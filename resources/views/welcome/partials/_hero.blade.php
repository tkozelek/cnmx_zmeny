<link rel="preload" as="image" fetchpriority="high"
      imagesrcset="{{ asset('images/welcome_hero_960.webp') }} 960w, {{ asset('images/welcome_hero_1600.webp') }} 1600w, {{ asset('images/welcome_hero_2400.webp') }} 2400w"
      imagesizes="100vw">

{{-- Height subtracts the sticky nav (~3.5rem) so the hero fills exactly one screen and
     the scroll cue at the bottom is actually visible. `svh` keeps mobile browsers'
     collapsing URL bar from pushing it off-screen. --}}
<section id="hero" class="relative flex min-h-[calc(100svh-3.5rem)] items-center justify-center overflow-hidden border-b border-neutral-800 text-center">
    {{-- A real <img> rather than a CSS background: it gets srcset (mobile never downloads
         the 2400px file), fetchpriority for LCP, and alt text. The photo is dark and
         already sits in the brand's blue, so a flat wash is enough for contrast - no
         gradient fade needed. --}}
    <img
        src="{{ asset('images/welcome_hero_1600.webp') }}"
        srcset="{{ asset('images/welcome_hero_960.webp') }} 960w, {{ asset('images/welcome_hero_1600.webp') }} 1600w, {{ asset('images/welcome_hero_2400.webp') }} 2400w"
        sizes="100vw"
        width="2400"
        height="1600"
        alt="Diváci v tmavej kinosále pred plátnom"
        fetchpriority="high"
        decoding="async"
        class="absolute inset-0 h-full w-full object-cover object-[50%_40%]"
    >
    <div class="absolute inset-0 bg-neutral-950/55"></div>

    <div id="hero-content" class="container relative mx-auto px-4 pb-24 pt-16 sm:pb-32">
        <p class="mb-5 inline-flex items-center gap-2 rounded-full border border-brand-400/30 bg-neutral-950/60 px-4 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] text-brand-300">
            <i class="fa-solid fa-film text-[0.7rem]" aria-hidden="true"></i>
            Plánovanie zmien pre kino
        </p>

        <h1 class="mx-auto max-w-4xl text-4xl font-extrabold leading-[1.1] tracking-tight text-neutral-50 sm:text-5xl md:text-6xl">
            Tvoje zmeny v kine, <span class="text-brand-400">prehľadne na jednom mieste.</span>
        </h1>

        <p class="mx-auto mb-10 mt-6 max-w-2xl text-lg leading-relaxed text-neutral-300 md:text-xl">
            Zapíš si, kedy môžeš pracovať, a rozpis máš vždy po ruke - na mobile aj na počítači.
        </p>

        <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="{{ route('register') }}" class="cta-button inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-lg bg-neutral-100 px-7 py-3 text-base font-semibold text-neutral-900 transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-950 sm:w-auto">
                <i class="fa-solid fa-user-plus text-sm" aria-hidden="true"></i>
                Vytvoriť účet
            </a>
            <a href="{{ route('login') }}" class="inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-lg border border-neutral-600 bg-neutral-950/60 px-7 py-3 text-base font-semibold text-neutral-100 transition hover:border-neutral-400 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-950 sm:w-auto">
                <i class="fa-solid fa-right-to-bracket text-sm" aria-hidden="true"></i>
                Prihlásiť sa
            </a>
        </div>
    </div>

    <a href="#ako-to-funguje" class="absolute bottom-6 left-1/2 flex -translate-x-1/2 flex-col items-center gap-1 text-xs font-medium text-neutral-400 transition hover:text-white">
        Ako to funguje
        <i class="fa-solid fa-chevron-down text-base motion-safe:animate-bounce" aria-hidden="true"></i>
    </a>
</section>
