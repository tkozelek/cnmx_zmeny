/**
 * Scroll choreography for the welcome page. Two kinds of motion, kept deliberately apart:
 *
 * - one-shot reveals (`.reveal`, the hero headline) - IntersectionObserver flips a class and
 *   CSS transitions do the rest, see the "Welcome-page motion" block in app.css;
 * - scroll-linked values (hero zoom/shade, the steps track, the progress hairline) - one
 *   passive scroll listener, batched into a single requestAnimationFrame per frame, writing
 *   only transform/opacity so nothing triggers layout.
 *
 * Native scrolling is never hijacked. With prefers-reduced-motion everything is shown in its
 * final state and the scroll-linked layer is skipped entirely.
 */

const clamp = (value, min = 0, max = 1) => Math.min(max, Math.max(min, value));

export function mountWelcome() {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const hero = document.getElementById('hero');

    // The hero headline plays on load, not on scroll - it is already on screen.
    requestAnimationFrame(() => hero?.classList.add('is-ready'));

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
    mountTilt(reducedMotion);

    if (reducedMotion) {
        return;
    }

    const heroMedia = document.getElementById('hero-media');
    const heroShade = document.getElementById('hero-shade');
    const heroContent = document.getElementById('hero-content');
    const heroCue = document.getElementById('hero-cue');
    const progressBar = document.querySelector('[data-scroll-progress]');
    let ticking = false;

    const update = () => {
        ticking = false;
        const scrollY = window.scrollY;
        const viewport = window.innerHeight;

        if (hero) {
            // Push into the room while the photo sinks into the dark page below it.
            const p = clamp(scrollY / (hero.offsetHeight || 1));
            heroMedia.style.transform = `scale(${1 + p * 0.12})`;
            // The shade outruns the scroll so the photo's bright bottom edge is already dark
            // by the time the next section meets it.
            heroShade.style.opacity = String(clamp(p * 1.6) * 0.92);
            heroContent.style.opacity = String(clamp(1 - p * 1.8));
            heroContent.style.transform = `translateY(${p * 70}px)`;
            heroCue.style.opacity = String(clamp(1 - p * 4));
        }

        const scrollable = document.documentElement.scrollHeight - viewport;
        progressBar.style.transform = `scaleX(${scrollable > 0 ? clamp(scrollY / scrollable) : 0})`;

        steps?.update(viewport);
    };

    const requestUpdate = () => {
        if (!ticking) {
            ticking = true;
            requestAnimationFrame(update);
        }
    };

    window.addEventListener('scroll', requestUpdate, { passive: true });
    window.addEventListener('resize', () => {
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

    const update = (viewport) => {
        const r = track.getBoundingClientRect();
        // Vertical: the fill follows a reading line at 65% of the viewport down the track.
        // Horizontal: the track is a hairline, so drive it by the section scrolling through
        // the lower part of the screen instead.
        const progress = horizontal
            ? clamp((viewport * 0.85 - r.top) / (viewport * 0.4))
            : clamp((viewport * 0.65 - r.top) / (r.height || 1));
        paint(progress);
    };

    layout();
    if (reducedMotion) {
        paint(1);
    } else {
        update(window.innerHeight);
    }

    return { layout, update };
}

/** A few degrees of 3D tilt toward the pointer on `[data-tilt]` cards - mouse only. */
function mountTilt(reducedMotion) {
    if (reducedMotion || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        return;
    }

    const MAX_DEG = 5;

    document.querySelectorAll('[data-tilt]').forEach((card) => {
        card.addEventListener('pointermove', (event) => {
            const r = card.getBoundingClientRect();
            const x = (event.clientX - r.left) / r.width - 0.5;
            const y = (event.clientY - r.top) / r.height - 0.5;
            card.style.transition = 'transform 150ms ease-out, border-color 150ms ease';
            card.style.transform = `perspective(900px) rotateX(${-y * MAX_DEG}deg) rotateY(${x * MAX_DEG}deg) translateY(-2px)`;
        });

        card.addEventListener('pointerleave', () => {
            card.style.transition = '';
            card.style.transform = '';
        });
    });
}
