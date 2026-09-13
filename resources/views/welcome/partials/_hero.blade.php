<link rel="preload" as="image" href="{{ asset('images/cinemax_landing_page_upscaled.jpg') }}" fetchpriority="high">

<section id="hero" class="hero-section relative flex h-screen items-center justify-center overflow-hidden text-center" style="background-image: url('{{ asset('images/cinemax_landing_page_upscaled.jpg') }}')">
    {{-- Dark wash so light text stays readable over any photo, and a bottom fade that
         blends straight into the neutral-950 body - the rest of the page is dark by
         default, the hero photo is the one exception, so it has to earn the transition. --}}
    <div class="absolute inset-0 bg-neutral-950/70"></div>
    <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-neutral-950 to-transparent"></div>

    <div id="hero-content" class="container relative mx-auto px-4">
        <h1 class="text-4xl font-extrabold leading-tight text-neutral-50 md:text-6xl">
            Vaša práca, váš čas. <span class="gradient-text">Váš rozvrh.</span>
        </h1>
        <p class="mx-auto mb-8 mt-4 max-w-2xl text-lg text-neutral-300 md:text-xl">
            Jednoduchý nástroj na plánovanie zmien, ktorý dáva zamestnancom kina flexibilitu
            a manažérom prehľad nad celým týždňom.
        </p>

        <div class="flex flex-col items-center justify-center gap-3 sm:flex-row">
            <a href="#registracia" class="cta-button inline-flex items-center gap-2 rounded-lg bg-neutral-100 px-6 py-3 text-base font-semibold text-neutral-900 transition hover:bg-white">
                <i class="fa-solid fa-user-plus text-sm"></i>
                Začať s plánovaním
            </a>
            <a href="#ako-to-funguje" class="inline-flex items-center gap-2 rounded-lg border border-neutral-700 bg-neutral-900/60 px-6 py-3 text-base font-semibold text-neutral-200 backdrop-blur transition hover:border-neutral-600 hover:text-white">
                Ako to funguje
                <i class="fa-solid fa-arrow-down text-sm"></i>
            </a>
        </div>
    </div>

    <a href="#ako-to-funguje" aria-label="Prejsť na ďalšiu sekciu" class="absolute bottom-6 left-1/2 -translate-x-1/2 text-neutral-400 transition hover:text-white">
        <i class="fa-solid fa-chevron-down animate-bounce text-xl"></i>
    </a>
</section>
