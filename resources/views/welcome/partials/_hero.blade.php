@php
    // Split into words server-side so each one can slide up from behind its own mask on
    // load; the accent flag paints the second half of the line in the brand color.
    $headline = [
        ['Tvoje', false], ['zmeny', false], ['v', false], ['kine,', false],
        ['prehľadne', true], ['na', true], ['jednom', true], ['mieste.', true],
    ];
@endphp

<link rel="preload" as="image" fetchpriority="high"
      imagesrcset="{{ asset('images/welcome_hero_960.webp') }} 960w, {{ asset('images/welcome_hero_1600.webp') }} 1600w, {{ asset('images/welcome_hero_2400.webp') }} 2400w"
      imagesizes="100vw">

{{-- Height subtracts the sticky nav (~3.5rem) so the hero fills exactly one screen and
     the scroll cue at the bottom is actually visible. `svh` keeps mobile browsers'
     collapsing URL bar from pushing it off-screen. --}}
<section id="hero" class="relative flex min-h-[calc(100svh-3.5rem)] items-center justify-center overflow-hidden text-center">
    {{-- A real <img> rather than a CSS background: it gets srcset (mobile never downloads
         the 2400px file), fetchpriority for LCP, and alt text. welcome.js slowly scales it
         and fades in the solid shade above it on scroll, so the photo sinks into the dark
         page instead of ending on a hard edge - no gradient needed. --}}
    <img
        id="hero-media"
        src="{{ asset('images/welcome_hero_1600.webp') }}"
        srcset="{{ asset('images/welcome_hero_960.webp') }} 960w, {{ asset('images/welcome_hero_1600.webp') }} 1600w, {{ asset('images/welcome_hero_2400.webp') }} 2400w"
        sizes="100vw"
        width="2400"
        height="1600"
        alt="Diváci v tmavej kinosále pred plátnom"
        fetchpriority="high"
        decoding="async"
        class="absolute inset-0 h-full w-full origin-[50%_40%] object-cover object-[50%_40%] will-change-transform"
    >
    <div class="absolute inset-0 bg-neutral-950/55"></div>
    <div id="hero-shade" class="absolute inset-0 bg-neutral-950 opacity-0"></div>

    <div id="hero-content" class="container relative mx-auto px-4 pb-24 pt-16 sm:pb-32">
        <h1 class="mx-auto max-w-4xl text-4xl font-extrabold leading-[1.1] tracking-tight text-neutral-50 sm:text-5xl md:text-6xl">
            @foreach($headline as $i => [$word, $isAccent])
                <span class="split-word"><span class="split-word-inner {{ $isAccent ? 'text-brand-400' : '' }}" style="--i: {{ $i }}">{{ $word }}</span></span>
            @endforeach
        </h1>

        <p class="hero-follow mx-auto mb-10 mt-6 max-w-2xl text-lg leading-relaxed text-neutral-300 md:text-xl" style="--delay: 550ms">
            Zapíš si, kedy môžeš pracovať, a rozpis máš vždy po ruke - na mobile aj na počítači.
        </p>

        <div class="hero-follow flex flex-col items-center justify-center gap-3 sm:flex-row" style="--delay: 700ms">
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

    {{-- The reveal sits on an inner span: .hero-follow sets `transform`, which would
         otherwise wipe out the -translate-x-1/2 that centers the link. --}}
    <a id="hero-cue" href="#ako-to-funguje" class="absolute bottom-6 left-1/2 -translate-x-1/2 text-xs font-medium text-neutral-400 transition hover:text-white">
        <span class="hero-follow flex flex-col items-center gap-1" style="--delay: 1100ms">
            Ako to funguje
            <i class="fa-solid fa-chevron-down text-base motion-safe:animate-bounce" aria-hidden="true"></i>
        </span>
    </a>
</section>
