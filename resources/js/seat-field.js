/**
 * Interactive auditorium for the welcome page: rows of cinema seats drawn in perspective on a
 * <canvas>, receding toward a thin "screen" line. Seats near the pointer lift and light up in the
 * brand blue, the room pans a little with the pointer, and clicking a seat takes it - the same
 * gesture as signing up for a shift. Without a pointer (touch, idle, first paint) a slow
 * wandering spot plays the cursor's part so the section is never dead.
 *
 * Plain 2D canvas with a hand-rolled projection instead of a WebGL library: a few hundred
 * rounded rects do not need a 3D engine, and the welcome page stays dependency-free.
 */

const MAX_COLUMNS = 26;
const MIN_COLUMNS = 12;
/** Roughly one seat per this many CSS pixels of canvas width, so seats stay tappable on phones. */
const PIXELS_PER_SEAT = 52;
const SEAT_SPACING = 1;
const ROW_SPACING = 1.25;
const NEAREST_DEPTH = 5;
const INFLUENCE_RADIUS = 2.6;
const MAX_LIFT = 0.55;
const IDLE_AFTER_MS = 3500;

/** Palette read from the same CSS variables Tailwind's `brand-*` classes use. */
function readPalette(el) {
    const style = getComputedStyle(el);
    const rgb = (name, fallback) => (style.getPropertyValue(name).trim() || fallback).split(/\s+/).map(Number);

    return {
        brand: rgb('--color-brand-500', '14 165 233'),
        brandLight: rgb('--color-brand-300', '125 211 252'),
        taken: rgb('--color-brand-400', '56 189 248'),
        seat: [38, 38, 38],     // neutral-800
        seatEdge: [64, 64, 64], // neutral-700
    };
}

const mix = (a, b, t) => a.map((v, i) => Math.round(v + (b[i] - v) * t));
const css = (c) => `rgb(${c[0]} ${c[1]} ${c[2]})`;

export function mountSeatField(canvas) {
    const ctx = canvas.getContext('2d');
    const palette = readPalette(canvas);
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    let seats = [];
    let rows = 0;
    let columns = 0;
    let roomWidth = 0;
    let backRowDepth = 0;
    let screenDepth = 0;
    let width = 0;
    let height = 0;
    let focal = 0;
    let horizon = 0;
    let cameraHeight = 0;
    let camX = 0;
    let targetCamX = 0;
    let pointer = null;
    let lastPointerAt = -Infinity;
    let running = false;
    let frame = 0;

    /**
     * Lays out the room for a given width: fewer, bigger seats on a phone, the full house on a
     * desktop. Two aisles split each row roughly 1:2:1. Row 0 is the back row, so iterating the
     * array in order paints back-to-front.
     */
    const buildSeats = (columnCount) => {
        columns = columnCount;
        rows = columns < 18 ? 8 : 11;
        const sideBlock = Math.round(columns * 0.27);
        const aisleAfter = [sideBlock - 1, columns - sideBlock - 1];

        seats = [];
        for (let row = 0; row < rows; row++) {
            let x = 0;
            for (let col = 0; col < columns; col++) {
                seats.push({ row, x, z: NEAREST_DEPTH + (rows - 1 - row) * ROW_SPACING, glow: 0, lift: 0, taken: false });
                x += SEAT_SPACING + (aisleAfter.includes(col) ? SEAT_SPACING * 0.9 : 0);
            }
        }
        roomWidth = seats[columns - 1].x;
        seats.forEach((seat) => { seat.x -= roomWidth / 2; });
        backRowDepth = NEAREST_DEPTH + (rows - 1) * ROW_SPACING;
        screenDepth = backRowDepth + 4;
    };

    const project = (x, z, lift = 0) => ({
        x: width / 2 + ((x - camX) * focal) / z,
        y: horizon + ((cameraHeight - lift) * focal) / z,
        scale: focal / z,
    });

    /** Inverse of project() on the floor plane: the world point under the pointer. */
    const unproject = (sx, sy) => {
        const z = (cameraHeight * focal) / Math.max(sy - horizon, 1);
        return { x: ((sx - width / 2) * z) / focal + camX, z };
    };

    const seatBox = (seat) => {
        const p = project(seat.x, seat.z, seat.lift);
        return { x: p.x, y: p.y, w: p.scale * 0.8, h: p.scale * 0.95, scale: p.scale };
    };

    const seatAt = (sx, sy) => {
        // Front rows overlap the ones behind them, so test front-to-back and take the first hit.
        for (let i = seats.length - 1; i >= 0; i--) {
            const b = seatBox(seats[i]);
            if (sx >= b.x - b.w / 2 && sx <= b.x + b.w / 2 && sy >= b.y - b.h && sy <= b.y) {
                return seats[i];
            }
        }
        return null;
    };

    const draw = () => {
        ctx.clearRect(0, 0, width, height);

        // The screen: one solid brand line, echoing the one in the hero photo.
        // Sits above the back row by the same gap the rows keep, so it reads as the far wall.
        const left = project(-roomWidth * 0.4, screenDepth);
        const right = project(roomWidth * 0.4, screenDepth);
        const screenY = project(0, backRowDepth).y - height * 0.16;
        ctx.fillStyle = css(palette.brandLight);
        ctx.fillRect(left.x, screenY, right.x - left.x, 3);

        for (const seat of seats) {
            const b = seatBox(seat);
            const fill = seat.taken ? palette.taken : mix(palette.seat, palette.brand, seat.glow);
            const edge = seat.taken ? palette.brandLight : mix(palette.seatEdge, palette.brandLight, seat.glow);

            // Back rows fade into the dark like a real auditorium; lit seats punch through.
            ctx.globalAlpha = Math.min(1, 0.3 + 0.7 * ((seat.row + 1) / rows) + seat.glow * 0.6);
            ctx.fillStyle = css(fill);
            ctx.strokeStyle = css(edge);
            ctx.lineWidth = Math.max(0.6, b.scale * 0.05);

            // Seat back, then the cushion as a flat bar below it.
            ctx.beginPath();
            ctx.roundRect(b.x - b.w / 2, b.y - b.h, b.w, b.h * 0.72, Math.max(1, b.w * 0.22));
            ctx.fill();
            ctx.stroke();
            ctx.fillRect(b.x - b.w * 0.55, b.y - b.h * 0.2, b.w * 1.1, Math.max(1, b.h * 0.12));
        }
        ctx.globalAlpha = 1;
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
        }

        // Fit the front row to the canvas width, raise the camera until the rows spread over
        // most of the canvas height, then drop the horizon so the front row sits at the bottom.
        focal = (width * 0.5 * NEAREST_DEPTH) / (roomWidth / 2 + 1);
        cameraHeight = (height * 0.72) / (focal * (1 / NEAREST_DEPTH - 1 / backRowDepth));
        horizon = height * 0.95 - (cameraHeight * focal) / NEAREST_DEPTH;
        draw();
    };

    const step = (time) => {
        if (!running) return;

        let focus = pointer;
        if (!focus || time - lastPointerAt > IDLE_AFTER_MS) {
            // Idle: a slow figure-eight sweep across the room.
            const t = time / 2600;
            focus = { x: Math.sin(t) * roomWidth * 0.34, z: NEAREST_DEPTH + rows * ROW_SPACING * (0.45 + 0.3 * Math.sin(t * 2)) };
            targetCamX = Math.sin(t) * 0.8;
        }

        camX += (targetCamX - camX) * 0.06;

        for (const seat of seats) {
            const dist = Math.hypot(seat.x - focus.x, (seat.z - focus.z) * 0.9);
            const influence = Math.max(0, 1 - dist / INFLUENCE_RADIUS) ** 2;
            seat.glow += (influence - seat.glow) * 0.12;
            seat.lift += ((seat.taken ? 0.18 : 0) + influence * MAX_LIFT - seat.lift) * 0.12;
        }

        draw();
        frame = requestAnimationFrame(step);
    };

    const start = () => {
        if (running || reducedMotion) return;
        running = true;
        frame = requestAnimationFrame(step);
    };

    const stop = () => {
        running = false;
        cancelAnimationFrame(frame);
    };

    const localPoint = (event) => {
        const rect = canvas.getBoundingClientRect();
        return [event.clientX - rect.left, event.clientY - rect.top];
    };

    canvas.addEventListener('pointermove', (event) => {
        const [sx, sy] = localPoint(event);
        pointer = unproject(sx, sy);
        lastPointerAt = performance.now();
        targetCamX = (sx / width - 0.5) * 2.4;
        canvas.style.cursor = seatAt(sx, sy) ? 'pointer' : 'default';
    });

    canvas.addEventListener('pointerleave', () => { pointer = null; });

    canvas.addEventListener('click', (event) => {
        const seat = seatAt(...localPoint(event));
        if (!seat) return;
        seat.taken = !seat.taken;
        if (reducedMotion) draw();
    });

    // Only animate while the section is on screen - no reason to burn frames below the fold.
    new IntersectionObserver(([entry]) => (entry.isIntersecting ? start() : stop()), { threshold: 0.05 }).observe(canvas);
    new ResizeObserver(resize).observe(canvas);
}
