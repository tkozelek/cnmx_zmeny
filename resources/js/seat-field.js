/**
 * Interactive auditorium: raked rows of cinema seats in real 3D (camera with yaw/pitch, hand-rolled
 * projection on a 2D canvas), facing a wide screen. Scrolling in cranes the camera down from a
 * bird's-eye view to a seat in the last row, the pointer turns the head, seats near it light up and
 * a click takes one - the same gesture as signing up for a shift.
 */

const MAX_COLUMNS = 18;
const MIN_COLUMNS = 10;
/** Roughly one seat per this many CSS pixels of canvas width, so seats stay tappable on phones. */
const PIXELS_PER_SEAT = 72;
const SEAT_SPACING = 1;
const ROW_SPACING = 1.45;
const ROW_RISE = 0.55;
const SCREEN_GAP = 4.5;
const CAMERA_BACK = 7;
const INFLUENCE_RADIUS = 2.4;
const MAX_LIFT = 0.25;
const TAKEN_LIFT = 0.12;
const IDLE_AFTER_MS = 3500;
// Motion nobody started must stop within 5 s (WCAG 2.2.2) - the sweep then rests until a pointer moves.
const IDLE_SWEEP_MS = 5000;
const ENTRANCE_MS = 900;
const ENTRANCE_ROW_STAGGER_MS = 60;

const NEUTRAL = {
    950: [10, 10, 10],
    900: [23, 23, 23],
    850: [31, 31, 31],
    800: [38, 38, 38],
    700: [64, 64, 64],
    600: [82, 82, 82],
};
const EMERALD = { 300: [110, 231, 183], 400: [52, 211, 153], 500: [16, 185, 129], 700: [4, 120, 87] };

function readPalette(el) {
    const style = getComputedStyle(el);
    const rgb = (shade) => style.getPropertyValue(`--color-brand-${shade}`).trim().split(/\s+/).map(Number);

    return Object.fromEntries([200, 300, 400, 500, 600, 700, 900, 950].map((shade) => [shade, rgb(shade)]));
}

const clamp01 = (v) => Math.min(1, Math.max(0, v));
const lerp = (a, b, t) => a + (b - a) * t;
const mix = (a, b, t) => [lerp(a[0], b[0], t), lerp(a[1], b[1], t), lerp(a[2], b[2], t)];
const css = (c) => `rgb(${Math.round(c[0])} ${Math.round(c[1])} ${Math.round(c[2])})`;
const easeInOut = (t) => (t < 0.5 ? 4 * t * t * t : 1 - (-2 * t + 2) ** 3 / 2);

export function mountSeatField(canvas) {
    const ctx = canvas.getContext('2d');
    const brand = readPalette(canvas);
    const label = document.querySelector('[data-seat-label]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let seats = [];
    let rows = 0;
    let columns = 0;
    let roomWidth = 0;
    let width = 0;
    let height = 0;
    let focal = 1;
    let centerY = 0;
    let order = [];

    const cam = { x: 0, y: 0, z: 0, yaw: 0, pitch: 0 };
    const target = { x: 0, crane: 1, yaw: 0 };
    let crane = reducedMotion ? 0 : 1;
    let cosY = 1;
    let sinY = 0;
    let cosP = 1;
    let sinP = 0;

    let hovered = null;
    let pointerInside = false;
    let pointerAt = [0, 0];
    let lastPointerAt = -Infinity;
    let screenFlash = 0;
    let running = false;
    let visible = false;
    let enteredAt = null;
    let lastTime = 0;
    let frame = 0;

    const lastRowZ = () => -(rows - 1) * ROW_SPACING;
    const floorY = (row) => row * ROW_RISE;
    const screen = () => {
        const halfWidth = roomWidth * 0.46;
        const bottom = 1.3;
        return { halfWidth, bottom, top: bottom + (halfWidth * 2) / 2.39, z: SCREEN_GAP };
    };

    /** Row 0 is the front row (nearest the screen), the camera sits behind the last one. */
    const buildSeats = (columnCount) => {
        columns = columnCount;
        rows = columns < 15 ? 7 : 10;
        const sideBlock = Math.round(columns * 0.25);
        const aisleAfter = [sideBlock - 1, columns - sideBlock - 1];

        seats = [];
        for (let row = 0; row < rows; row++) {
            let x = 0;
            for (let col = 0; col < columns; col++) {
                seats.push({ row, col, x, z: -row * ROW_SPACING, glow: 0, lift: 0, enter: reducedMotion ? 1 : 0, taken: false });
                x += SEAT_SPACING + (aisleAfter.includes(col) ? SEAT_SPACING * 0.9 : 0);
            }
        }
        roomWidth = seats[columns - 1].x;
        seats.forEach((seat) => { seat.x -= roomWidth / 2; });
    };

    /** Camera pose for a crane value: 0 = standing behind the last row, 1 = high above the room. */
    const pose = (craneValue) => {
        const t = easeInOut(craneValue);
        const lookAt = { y: lerp(screen().bottom, floorY(rows * 0.45), t), z: lerp(SCREEN_GAP * 0.35, -rows * ROW_SPACING * 0.45, t) };
        const position = {
            y: floorY(rows - 1) + lerp(8.5, rows * 3, t),
            z: lastRowZ() - lerp(CAMERA_BACK, CAMERA_BACK + rows * 0.5, t),
        };
        return { ...position, pitch: Math.atan2(position.y - lookAt.y, lookAt.z - position.z) };
    };

    const setCamera = () => {
        const p = pose(crane);
        cam.y = p.y;
        cam.z = p.z;
        cam.pitch = p.pitch;
        cosY = Math.cos(cam.yaw);
        sinY = Math.sin(cam.yaw);
        cosP = Math.cos(cam.pitch);
        sinP = Math.sin(cam.pitch);
    };

    /** World -> screen. Returns null behind the camera. Positive pitch looks down. */
    const project = (x, y, z) => {
        const dx = x - cam.x;
        const dy = y - cam.y;
        const dz = z - cam.z;
        const rx = dx * cosY - dz * sinY;
        const rz = dx * sinY + dz * cosY;
        const ry = dy * cosP + rz * sinP;
        const depth = -dy * sinP + rz * cosP;
        if (depth < 0.2) return null;
        return { x: width / 2 + (rx * focal) / depth, y: centerY - (ry * focal) / depth };
    };

    /** Fits the resting pose into the canvas once per resize; the crane then moves within that lens. */
    const fit = () => {
        const saved = crane;
        crane = 0;
        cam.x = 0;
        cam.yaw = 0;
        focal = 1;
        centerY = 0;
        setCamera();
        const s = screen();
        const edge = roomWidth / 2 + 0.5;
        const points = [
            project(-s.halfWidth, s.top, s.z), project(s.halfWidth, s.top, s.z),
            project(-edge, floorY(rows - 1), lastRowZ() - 0.2), project(edge, floorY(rows - 1), lastRowZ() - 0.2),
            project(-edge, floorY(0), 0), project(edge, floorY(0), 0),
        ].filter(Boolean);
        const xs = points.map((p) => p.x - width / 2);
        const ys = points.map((p) => p.y);
        const spanX = Math.max(...xs) - Math.min(...xs);
        const spanY = Math.max(...ys) - Math.min(...ys);
        focal = Math.min((width * 0.96) / spanX, (height * 0.92) / spanY);
        centerY = height * 0.5 - ((Math.max(...ys) + Math.min(...ys)) / 2) * focal;
        crane = saved;
        setCamera();
    };

    const path = (points) => {
        if (points.some((p) => !p)) return false;
        ctx.beginPath();
        ctx.moveTo(points[0].x, points[0].y);
        for (let i = 1; i < points.length; i++) {
            ctx.lineTo(points[i].x, points[i].y);
        }
        ctx.closePath();
        return true;
    };

    const fillPath = (points, color) => {
        if (path(points)) {
            ctx.fillStyle = css(color);
            ctx.fill();
        }
    };

    /** The faces of an axis-aligned box the camera can see, in drawing order: one flank, its top, its back (camera side). */
    const boxFaces = (out, x0, x1, y0, y1, z0, z1, colors) => {
        if (cam.x < x0) {
            out.push([[project(x0, y0, z0), project(x0, y0, z1), project(x0, y1, z1), project(x0, y1, z0)], colors.side]);
        } else if (cam.x > x1) {
            out.push([[project(x1, y0, z0), project(x1, y0, z1), project(x1, y1, z1), project(x1, y1, z0)], colors.side]);
        }
        if (cam.y > y1) {
            out.push([[project(x0, y1, z0), project(x1, y1, z0), project(x1, y1, z1), project(x0, y1, z1)], colors.top]);
        }
        out.push([[project(x0, y0, z0), project(x1, y0, z0), project(x1, y1, z0), project(x0, y1, z0)], colors.back]);
    };

    const seatBase = (seat, lift = seat.lift) => floorY(seat.row) + lift - (1 - seat.enter) * 1.6;

    const seatColors = (seat, shade) => {
        if (seat.taken) {
            return { back: EMERALD[500], top: EMERALD[300], side: EMERALD[700], cushion: EMERALD[400] };
        }
        // Rows near the screen catch its light; the ones beside the camera stay silhouettes.
        const spill = (1 - seat.row / (rows - 1)) * 0.45 * shade;
        return {
            back: mix(mix(NEUTRAL[800], brand[900], spill), brand[500], seat.glow),
            top: mix(mix(NEUTRAL[600], brand[300], spill * 0.7), brand[200], seat.glow),
            side: mix(NEUTRAL[900], brand[700], seat.glow),
            cushion: mix(mix(NEUTRAL[700], brand[700], spill * 0.6), brand[400], seat.glow),
        };
    };

    /** One chair as drawn: cushion, armrests, then the backrest, which is nearest the camera. Picking uses the same faces. */
    const seatFaces = (seat, c = {}, lift = seat.lift) => {
        const out = [];
        const y = seatBase(seat, lift);
        const { x, z } = seat;
        boxFaces(out, x - 0.38, x + 0.38, y + 0.3, y + 0.42, z, z + 0.5, { back: c.cushion, top: c.cushion, side: c.side });
        boxFaces(out, x - 0.5, x - 0.44, y + 0.3, y + 0.6, z - 0.04, z + 0.46, { back: c.side, top: c.top, side: c.side });
        boxFaces(out, x + 0.44, x + 0.5, y + 0.3, y + 0.6, z - 0.04, z + 0.46, { back: c.side, top: c.top, side: c.side });
        boxFaces(out, x - 0.4, x + 0.4, y + 0.3, y + 1.08, z - 0.14, z, c);
        return out;
    };

    const drawSeat = (seat, shade) => {
        for (const [points, color] of seatFaces(seat, seatColors(seat, shade))) {
            fillPath(points, color);
        }
    };

    const draw = () => {
        ctx.clearRect(0, 0, width, height);
        const s = screen();
        const shade = 0.6 + screenFlash * 0.4;
        const edge = roomWidth / 2 + 0.9;

        fillPath([project(-edge, 0, 0.6), project(edge, 0, 0.6), project(edge, 0, s.z), project(-edge, 0, s.z)], NEUTRAL[900]);
        // The screen's light on the stage in front of it.
        fillPath([project(-s.halfWidth, 0, s.z), project(s.halfWidth, 0, s.z), project(s.halfWidth * 1.1, 0, 1.2), project(-s.halfWidth * 1.1, 0, 1.2)], mix(brand[950], brand[900], 0.4 + screenFlash * 0.6));
        fillPath([project(-s.halfWidth, s.bottom, s.z), project(s.halfWidth, s.bottom, s.z), project(s.halfWidth, s.top, s.z), project(-s.halfWidth, s.top, s.z)], mix(mix(brand[950], brand[600], 0.42), brand[400], screenFlash * 0.75));
        fillPath([project(-s.halfWidth, s.top - 0.06, s.z), project(s.halfWidth, s.top - 0.06, s.z), project(s.halfWidth, s.top, s.z), project(-s.halfWidth, s.top, s.z)], brand[300]);

        // Painter's order: front row first, and within a row the seats furthest from the camera first.
        for (let row = 0; row < rows; row++) {
            const tread = -row * ROW_SPACING;
            fillPath([project(-edge, floorY(row), tread + 0.75), project(edge, floorY(row), tread + 0.75), project(edge, floorY(row), tread - 0.7), project(-edge, floorY(row), tread - 0.7)], NEUTRAL[900]);
            for (const col of order) {
                const seat = seats[row * columns + col];
                if (seat.enter <= 0.001) continue;
                ctx.globalAlpha = seat.enter;
                drawSeat(seat, shade);
            }
            ctx.globalAlpha = 1;
        }
    };

    const updateOrder = () => {
        order = [...Array(columns).keys()].sort((a, b) => Math.abs(seats[b].x - cam.x) - Math.abs(seats[a].x - cam.x));
    };

    const inside = (points, sx, sy) => {
        if (points.some((p) => !p)) return false;
        let hit = false;
        for (let i = 0, j = points.length - 1; i < points.length; j = i++) {
            const a = points[i];
            const b = points[j];
            if ((a.y > sy) !== (b.y > sy) && sx < ((b.x - a.x) * (sy - a.y)) / (b.y - a.y) + a.x) {
                hit = !hit;
            }
        }
        return hit;
    };

    const hits = (seat, sx, sy, lift) => seat.enter > 0.5 && seatFaces(seat, {}, lift).some(([points]) => inside(points, sx, sy));

    /**
     * The seat drawn under a screen point: the reverse of the painter's order, against the drawn faces.
     * The hovered seat also keeps its resting outline, so its own lift can't slide it out from under the pointer.
     */
    const seatAt = (sx, sy) => {
        for (let row = rows - 1; row >= 0; row--) {
            for (let i = order.length - 1; i >= 0; i--) {
                const seat = seats[row * columns + order[i]];
                if (hits(seat, sx, sy) || (seat === hovered && hits(seat, sx, sy, seat.taken ? TAKEN_LIFT : 0))) {
                    return seat;
                }
            }
        }
        return null;
    };

    const scrollCrane = () => {
        const rect = canvas.getBoundingClientRect();
        const viewport = window.innerHeight;
        // 1 while the canvas top enters the screen, 0 once its middle reaches 60% of the viewport.
        return clamp01((rect.top + rect.height * 0.5 - viewport * 0.6) / (viewport * 0.55));
    };

    const resize = () => {
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        const rect = canvas.getBoundingClientRect();
        width = rect.width;
        height = rect.height;
        canvas.width = Math.round(width * dpr);
        canvas.height = Math.round(height * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

        const wantedColumns = Math.min(MAX_COLUMNS, Math.max(MIN_COLUMNS, Math.round(width / PIXELS_PER_SEAT)));
        if (wantedColumns !== columns) {
            buildSeats(wantedColumns);
            // A rebuild after the entrance (rotation, resize) must not leave an empty room while the loop rests.
            if (enteredAt !== null && !running) {
                seats.forEach((seat) => { seat.enter = 1; });
            }
            updateLabel(null);
        }

        fit();
        updateOrder();
        draw();
    };

    const cameraSettled = () => Math.abs(target.crane - crane) < 0.001 && Math.abs(target.x - cam.x) < 0.005 && Math.abs(target.yaw - cam.yaw) < 0.0005 && screenFlash < 0.005;

    const step = (time) => {
        if (!running) return;

        enteredAt ??= time;

        const dt = Math.min((time - (lastTime || time)) / 1000, 0.05);
        lastTime = time;
        const ease = (k) => 1 - Math.exp(-k * dt);

        target.crane = scrollCrane();

        let focus = hovered ? { x: hovered.x, z: hovered.z } : null;
        let sweeping = false;
        if (!pointerInside && time - lastPointerAt > IDLE_AFTER_MS) {
            const idleSince = Math.max(enteredAt, lastPointerAt + IDLE_AFTER_MS);
            hovered = null;
            focus = null;
            target.x = 0;
            target.yaw = 0;
            if (time - idleSince < IDLE_SWEEP_MS) {
                const t = (time - enteredAt) / 1800;
                focus = { x: Math.sin(t) * roomWidth * 0.32, z: -rows * ROW_SPACING * (0.5 + 0.3 * Math.sin(t * 1.7)) };
                sweeping = true;
                target.x = Math.sin(t) * 0.6;
                target.yaw = Math.sin(t) * 0.04;
            }
        }

        crane += (target.crane - crane) * ease(5);
        cam.x += (target.x - cam.x) * ease(4);
        cam.yaw += (target.yaw - cam.yaw) * ease(4);
        screenFlash += (0 - screenFlash) * ease(2.2);
        setCamera();
        updateOrder();
        // The room moves under a still pointer too - keep the highlight on the seat a click would take.
        if (pointerInside) {
            hovered = seatAt(...pointerAt);
        }

        let seatsMoving = false;
        for (const seat of seats) {
            const nearby = focus ? Math.max(0, 1 - Math.hypot(seat.x - focus.x, (seat.z - focus.z) * 0.8) / INFLUENCE_RADIUS) ** 2 : 0;
            // Under the pointer only the hovered seat lights fully - it is exactly the one a click takes.
            const influence = sweeping ? nearby : seat === hovered ? 1 : nearby * 0.3;
            const entranceT = clamp01((time - enteredAt - seat.row * ENTRANCE_ROW_STAGGER_MS) / ENTRANCE_MS);
            seat.enter = 1 - (1 - entranceT) ** 4;
            const lift = (seat.taken ? TAKEN_LIFT : 0) + (sweeping || seat === hovered ? influence * MAX_LIFT : 0);
            seatsMoving ||= seat.enter < 0.999 || Math.abs(influence - seat.glow) > 0.005 || Math.abs(lift - seat.lift) > 0.002;
            seat.glow += (influence - seat.glow) * ease(9);
            seat.lift += (lift - seat.lift) * ease(9);
        }

        draw();

        if (!sweeping && !seatsMoving && cameraSettled()) {
            stop();
            return;
        }

        frame = requestAnimationFrame(step);
    };

    const start = () => {
        if (running || reducedMotion) return;
        running = true;
        lastTime = 0;
        frame = requestAnimationFrame(step);
    };

    const stop = () => {
        running = false;
        cancelAnimationFrame(frame);
    };

    function updateLabel(seat) {
        if (!label) return;
        const yours = seats.filter((s) => s.taken).length;
        label.classList.toggle('is-taken', yours > 0);
        if (seat?.taken) {
            label.textContent = `Rad ${rows - seat.row}, miesto ${seat.col + 1} je tvoje.`;
        } else if (yours === 0) {
            label.textContent = label.dataset.idle;
        }
    }

    const localPoint = (event) => {
        const rect = canvas.getBoundingClientRect();
        return [event.clientX - rect.left, event.clientY - rect.top];
    };

    canvas.addEventListener('pointermove', (event) => {
        const [sx, sy] = localPoint(event);
        pointerAt = [sx, sy];
        hovered = seatAt(sx, sy);
        pointerInside = true;
        lastPointerAt = performance.now();
        if (event.pointerType === 'mouse') {
            target.x = (sx / width - 0.5) * 1.6;
            target.yaw = (sx / width - 0.5) * 0.12;
        }
        start();
        canvas.style.cursor = hovered ? 'pointer' : 'default';
    });

    canvas.addEventListener('pointerleave', () => {
        hovered = null;
        pointerInside = false;
        target.x = 0;
        target.yaw = 0;
        start();
    });

    canvas.addEventListener('click', (event) => {
        const seat = seatAt(...localPoint(event));
        if (!seat) return;
        seat.taken = !seat.taken;
        if (seat.taken) {
            screenFlash = 1;
        }
        updateLabel(seat);
        if (reducedMotion) {
            seat.lift = seat.taken ? TAKEN_LIFT : 0;
            draw();
        } else {
            start();
        }
    });

    window.addEventListener('scroll', () => { if (visible) start(); }, { passive: true });

    // Only animate while the section is on screen - no reason to burn frames below the fold.
    new IntersectionObserver(([entry]) => {
        visible = entry.isIntersecting;
        visible ? start() : stop();
    }, { threshold: 0.02 }).observe(canvas);
    new ResizeObserver(resize).observe(canvas);
}
