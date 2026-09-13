<x-layout
    title="Plánovanie zmien pre kino"
    description="Jednoduchý nástroj na plánovanie zmien, ktorý dáva zamestnancom kina flexibilitu a manažérom prehľad nad celým týždňom."
>
    {{-- Hero Section --}}
    @include('welcome.partials._hero')

    {{-- How It Works Section --}}
    @include('welcome.partials._howitworks')

    {{-- Benefits Section --}}
    @include('welcome.partials._benefits')

    {{-- CTA / Register Section --}}
    @include('welcome.partials._cta')

    {{-- Footer --}}
    <footer class="border-t border-neutral-800 bg-neutral-950">
        <div class="container mx-auto px-4 py-6 text-center text-sm text-neutral-500">
            <p>&copy; {{ now()->year }} Cinemax Zmeny. Všetky práva vyhradené.</p>
        </div>
    </footer>

    <script>
        // Reveal-on-scroll for every `.reveal`/`.reveal-line` element, plus a subtle
        // scroll-linked fade+drift on the hero copy. IntersectionObserver instead of a
        // scroll-event poll: the browser tells us when a section enters view rather than
        // us checking getBoundingClientRect on every scroll tick.
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('active');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.15 });

        document.querySelectorAll('.reveal, .reveal-line').forEach((el) => revealObserver.observe(el));

        const heroContent = document.getElementById('hero-content');
        if (heroContent && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            const hero = document.getElementById('hero');
            let ticking = false;

            const updateHeroParallax = () => {
                const heroHeight = hero.offsetHeight || 1;
                const progress = Math.min(Math.max(window.scrollY / heroHeight, 0), 1);
                heroContent.style.opacity = String(1 - progress);
                heroContent.style.transform = `translateY(${progress * 60}px)`;
                ticking = false;
            };

            window.addEventListener('scroll', () => {
                if (!ticking) {
                    window.requestAnimationFrame(updateHeroParallax);
                    ticking = true;
                }
            }, { passive: true });

            updateHeroParallax();
        }
    </script>
</x-layout>
