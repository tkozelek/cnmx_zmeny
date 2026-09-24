@php
    $steps = [
        ['icon' => 'fa-user-plus', 'title' => '1. Založíš si účet', 'text' => 'Vyplníš krátky formulár - meno, e-mail, heslo.'],
        ['icon' => 'fa-envelope-circle-check', 'title' => '2. Potvrdíš e-mail', 'text' => 'Klikneš na odkaz, ktorý ti pošleme, aby sme vedeli, že účet je tvoj.'],
        ['icon' => 'fa-user-check', 'title' => '3. Počkáš na schválenie', 'text' => 'Manažér kina ti účet potvrdí, potom sa už len prihlásiš.'],
        ['icon' => 'fa-calendar-days', 'title' => '4. Zapíšeš si zmeny', 'text' => 'Označíš dni, kedy môžeš pracovať, a sleduješ, ako to manažér rozpísal.'],
    ];
@endphp

<section id="ako-to-funguje" class="bg-neutral-950 py-24 sm:py-32">
    <div class="container mx-auto px-4">
        <div class="reveal mb-16 text-center lg:mb-20">
            <h2 class="mb-3 text-3xl font-bold text-neutral-100 md:text-4xl">
                <span class="split-word"><span class="split-word-inner">Ako to funguje</span></span>
            </h2>
            <p class="mx-auto max-w-2xl text-neutral-400">Štyri kroky od registrácie po prvú zmenu v rozpise.</p>
        </div>

        {{-- One track runs through every icon's center - vertical on phones (steps stacked
             beside it), horizontal from `lg`. welcome.js measures the first and last icon to
             place it, then fills it as the section scrolls through the viewport and lights
             each step once the fill reaches it. --}}
        <div data-steps class="relative mx-auto grid max-w-md grid-cols-1 gap-12 lg:max-w-none lg:grid-cols-4 lg:gap-8">
            <div data-steps-track class="pointer-events-none absolute bg-neutral-800" aria-hidden="true">
                <div data-steps-fill class="h-full w-full origin-top bg-brand-400 lg:origin-left"></div>
            </div>

            @foreach($steps as $i => $step)
                <div class="step relative flex items-start gap-5 lg:flex-col lg:items-center lg:text-center">
                    <div data-step-icon class="step-icon relative flex h-20 w-20 shrink-0 items-center justify-center rounded-full border-2 border-brand-500/60 bg-neutral-900 text-2xl text-brand-400">
                        <i class="fa-solid {{ $step['icon'] }}" aria-hidden="true"></i>
                    </div>
                    <div class="step-copy pt-3 lg:pt-0">
                        <h3 class="mb-2 text-xl font-semibold text-neutral-100">{{ $step['title'] }}</h3>
                        <p class="text-neutral-400 lg:mx-auto lg:max-w-[15rem]">{{ $step['text'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
