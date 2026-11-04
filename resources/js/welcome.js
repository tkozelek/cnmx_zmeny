/**
 * Scroll choreography for the welcome page. Two kinds of motion, kept deliberately apart:
 *
 * - one-shot reveals (`.reveal`) - IntersectionObserver flips a class and CSS transitions do
 *   the rest, see the "Welcome-page motion" block in app.css (the hero headline is CSS-only);
 * - scroll-linked values (hero zoom/shade, the steps track, the progress hairline) - one
 *   passive scroll listener, batched into a single requestAnimationFrame per frame, writing
 *   only transform/opacity so nothing triggers layout.
 *
 * Native scrolling is never hijacked. With prefers-reduced-motion everything is shown in its
 * final state and the scroll-linked layer is skipped entirely.
 */

const clamp = (value, min = 0, max = 1) => Math.min(max, Math.max(min, value));
const HERO_DRIFT_PX = 14;

export function mountWelcome() {
    document.documentElement.classList.add('motion-ready');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const hero = document.getElementById('hero');

    const revealObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('active');
                revealObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });
    document.querySelectorAll('.reveal').forEach((el) => revealObserver.observe(el));

    const steps = mountSteps(reducedMotion);
    const weekBoard = mountWeekScene(reducedMotion);

    if (reducedMotion) {
        return;
    }

    const heroMedia = document.getElementById('hero-media');
    const heroShade = document.getElementById('hero-shade');
    const heroContent = document.getElementById('hero-content');
    const heroCue = document.getElementById('hero-cue');
    const heroHeadline = heroContent?.querySelector('h1');
    const progressBar = document.querySelector('[data-scroll-progress]');
    const drift = mountPointerDrift(hero, () => requestUpdate());
    const boardDrift = mountPointerDrift(weekBoard?.scene, () => requestUpdate());
    const hasHero = hero && heroMedia && heroShade && heroContent && heroCue;
    let ticking = false;
    let heroParked = false;

    const update = () => {
        ticking = false;

        // Every layout read first, then only writes - no forced layout in the frame.
        const scrollY = window.scrollY;
        const viewport = window.innerHeight;
        const heroHeight = hero?.offsetHeight || 1;
        const scrollable = document.documentElement.scrollHeight - viewport;
        const stepsProgress = steps?.measure(viewport);
        const boardTop = weekBoard?.stage.getBoundingClientRect().top;
        const heroInView = scrollY < heroHeight;

        // Once the hero is scrolled past, its final state is written once and then left alone.
        if (hasHero && (heroInView || !heroParked)) {
            heroParked = !heroInView;
            // Parallax: the photo trails the page at 40% speed (the section's overflow-hidden
            // clips what it pushes down), pushing into the room while it sinks into the dark.
            // The pointer drift moves it a few px the other way, so the room has depth at rest too.
            const p = clamp(scrollY / heroHeight);
            const { x, y } = drift.offset();
            heroMedia.style.transform = `translate3d(${x * HERO_DRIFT_PX}px, ${Math.min(scrollY, heroHeight) * 0.4 + y * HERO_DRIFT_PX * 0.6}px, 0) scale(${1.04 + p * 0.12})`;
            // The hero's mask already feathers the bottom edge, so the shade can trail the
            // scroll and leave the parallax visible for most of the way out.
            heroShade.style.opacity = String(clamp(p * 1.2) * 0.85);
            const contentOpacity = clamp(1 - p * 1.8);
            heroContent.style.opacity = String(contentOpacity);
            // The copy turns toward the pointer against the photo's drift, then tips back and
            // recedes into the room as the page scrolls on.
            heroContent.style.transform = `perspective(1100px) translate3d(0, ${p * 40}px, ${p * -260}px) rotateX(${p * 22 + y * 3}deg) rotateY(${x * -4}deg)`;
            heroHeadline.style.transform = `translate3d(${x * -10}px, ${y * -6}px, 0)`;
            // Faded-out buttons must not take blind clicks; keyboard focus scrolls them back into view.
            heroContent.style.pointerEvents = contentOpacity <= 0.05 ? 'none' : '';
            const cueOpacity = clamp(1 - p * 4);
            heroCue.style.opacity = String(cueOpacity);
            heroCue.style.pointerEvents = cueOpacity <= 0.05 ? 'none' : '';
        }

        if (weekBoard && boardTop !== undefined) {
            // The board lies back like a sheet on a desk and stands up as it scrolls in.
            const enter = clamp((viewport - boardTop) / (viewport * 0.7));
            const rise = enter * enter * (3 - 2 * enter);
            const { x, y } = boardDrift.offset();
            weekBoard.board.style.transform = `perspective(1400px) translateY(${(1 - rise) * 60}px) rotateX(${(1 - rise) * 38 + y * -4}deg) rotateY(${x * 6}deg) rotateZ(${(1 - rise) * -5}deg) scale(${0.9 + rise * 0.1})`;
        }

        if (progressBar) {
            progressBar.style.transform = `scaleX(${scrollable > 0 ? clamp(scrollY / scrollable) : 0})`;
        }

        if (stepsProgress !== undefined) {
            steps.paint(stepsProgress);
        }
    };

    const requestUpdate = () => {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(update);
        }
    };

    hero?.addEventListener('focusin', () => {
        if (window.scrollY > 0) {
            window.scrollTo({ top: 0 });
        }
    });

    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', () => {
        steps?.layout();
        requestUpdate();
    });
    // Inter swaps in after first paint and rewraps the step texts - re-measure the track then.
    document.fonts?.ready.then(() => {
        steps?.layout();
        requestUpdate();
    });
    update();
}

/**
 * "How it works": a track through the icon centers that fills with scroll progress and
 * lights every step it has reached. Vertical below `lg`, horizontal from `lg`.
 */
function mountSteps(reducedMotion) {
    const container = document.querySelector('[data-steps]');
    if (!container) {
        return null;
    }

    const track = container.querySelector('[data-steps-track]');
    const fill = container.querySelector('[data-steps-fill]');
    const stepEls = [...container.querySelectorAll('.step')];
    const icons = [...container.querySelectorAll('[data-step-icon]')];
    let horizontal = false;
    let stops = [];

    const layout = () => {
        const box = container.getBoundingClientRect();
        const centers = icons.map((icon) => {
            const r = icon.getBoundingClientRect();
            return { x: r.left + r.width / 2 - box.left, y: r.top + r.height / 2 - box.top };
        });
        const first = centers[0];
        const last = centers[centers.length - 1];
        horizontal = Math.abs(last.x - first.x) > Math.abs(last.y - first.y);

        Object.assign(track.style, horizontal
            ? { left: `${first.x}px`, top: `${first.y - 1}px`, width: `${last.x - first.x}px`, height: '2px' }
            : { left: `${first.x - 1}px`, top: `${first.y}px`, width: '2px', height: `${last.y - first.y}px` });

        // Where along the track (0..1) each step sits, so it lights exactly as the fill arrives.
        const length = horizontal ? last.x - first.x : last.y - first.y;
        stops = centers.map((c) => (horizontal ? c.x - first.x : c.y - first.y) / (length || 1));
    };

    const paint = (progress) => {
        fill.style.transform = horizontal ? `scaleX(${progress})` : `scaleY(${progress})`;
        stepEls.forEach((step, i) => step.classList.toggle('is-lit', progress >= stops[i] - 0.001));
    };

    const measure = (viewport) => {
        const r = track.getBoundingClientRect();
        // Vertical: the fill follows a reading line at 65% of the viewport down the track.
        // Horizontal: the track is a hairline, so drive it by the section scrolling through
        // the lower part of the screen instead.
        return horizontal
            ? clamp((viewport * 0.85 - r.top) / (viewport * 0.4))
            : clamp((viewport * 0.65 - r.top) / (r.height || 1));
    };

    layout();
    paint(reducedMotion ? 1 : measure(window.innerHeight));

    return { layout, measure, paint };
}

/**
 * Pointer position over an element as -1..1 offsets from its centre (mouse only), eased over time so
 * it glides after the hand stops. It only produces the numbers; the scroll update owns the transforms.
 */
function mountPointerDrift(el, requestUpdate) {
    const current = { x: 0, y: 0 };
    const target = { x: 0, y: 0 };

    if (!el || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        return { offset: () => current };
    }

    let frame = 0;
    let lastTime = 0;

    const step = (time) => {
        const dt = Math.min((time - lastTime) / 1000, 0.05);
        lastTime = time;
        const ease = 1 - Math.exp(-4 * dt);
        current.x += (target.x - current.x) * ease;
        current.y += (target.y - current.y) * ease;
        requestUpdate();

        const settled = Math.abs(target.x - current.x) < 0.003 && Math.abs(target.y - current.y) < 0.003;
        frame = settled ? 0 : requestAnimationFrame(step);
    };

    const aim = (x, y) => {
        target.x = x;
        target.y = y;
        if (!frame) {
            lastTime = performance.now();
            frame = requestAnimationFrame(step);
        }
    };

    el.addEventListener('pointermove', (event) => {
        const rect = el.getBoundingClientRect();
        aim(1 - ((event.clientX - rect.left) / rect.width) * 2, 1 - ((event.clientY - rect.top) / rect.height) * 2);
    });
    el.addEventListener('pointerleave', () => aim(0, 0));

    return { offset: () => current };
}

/**
 * "Tvoj týždeň v kine": the board sticks while the steps scroll past, and the step crossing the
 * reading line switches the board to its state - every `[data-from="n"]` part shows from step n
 * on. On phones the board covers the list, so the active step is repeated in a caption under it.
 * Off with reduced motion or on a screen too short for a sticky board (re-checked on rotation);
 * CSS then shows the finished week.
 */
function mountWeekScene(reducedMotion) {
    const scene = document.querySelector('[data-week-scene]');
    if (!scene || reducedMotion) {
        return;
    }

    const parts = [...scene.querySelectorAll('[data-from]')];
    const stepEls = [...scene.querySelectorAll('[data-scene-step]')];
    const captionTitle = scene.querySelector('[data-scene-caption-title]');
    const captionText = scene.querySelector('[data-scene-caption-text]');
    const tallEnough = window.matchMedia('(min-height: 42rem)');
    const desktop = window.matchMedia('(min-width: 1024px)');
    let observer = null;

    const show = (state) => {
        parts.forEach((part) => part.classList.toggle('is-on', Number(part.dataset.from) <= state));
        stepEls.forEach((step) => step.classList.toggle('is-active', Number(step.dataset.sceneStep) === state));

        const active = stepEls[state - 1];
        if (captionTitle && captionText) {
            captionTitle.textContent = active?.querySelector('h3')?.textContent ?? '';
            captionText.textContent = active?.querySelector('p')?.textContent ?? '';
        }
    };

    const setup = () => {
        observer?.disconnect();
        observer = null;

        if (!tallEnough.matches) {
            scene.classList.remove('is-live');
            return;
        }

        scene.classList.add('is-live');

        // Desktop: a step takes over when its text reaches the middle, beside the board's centre.
        // Phones: the steps are only spacers, so whichever one covers the middle of the screen.
        observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    show(Number(entry.target.dataset.sceneStep));
                }
            });
        }, { rootMargin: desktop.matches ? '-20% 0px -79% 0px' : '-50% 0px -49% 0px' });

        stepEls.forEach((step) => observer.observe(step));
    };

    tallEnough.addEventListener('change', setup);
    desktop.addEventListener('change', setup);
    setup();

    return { scene, board: scene.querySelector('[data-week-board]'), stage: scene.querySelector('.week-scene__stage') };
}
