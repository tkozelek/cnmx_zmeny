@php
    $steps = [
        ['icon' => 'fa-user-plus', 'title' => 'Založíš si účet', 'text' => 'Vyplníš krátky formulár: meno, e-mail, heslo.'],
        ['icon' => 'fa-envelope-circle-check', 'title' => 'Potvrdíš e-mail', 'text' => 'Klikneš na odkaz, ktorý ti pošleme, aby sme vedeli, že účet je tvoj.'],
        ['icon' => 'fa-user-check', 'title' => 'Počkáš na schválenie', 'text' => 'Manažér kina ti účet potvrdí, potom sa už len prihlásiš.'],
        ['icon' => 'fa-calendar-days', 'title' => 'Zapíšeš si zmeny', 'text' => 'Označíš dni, kedy môžeš pracovať, a vidíš, kto ďalší je zapísaný.'],
    ];
@endphp

<section id="registracia" class="relative overflow-hidden bg-neutral-950 pt-28 sm:pt-40">
    <div class="container relative z-10 mx-auto px-4">
        <div class="reveal mx-auto mb-16 max-w-2xl text-center lg:mb-20">
            <h2 class="mb-3 text-3xl font-bold text-neutral-100 md:text-5xl">
                <span class="split-word"><span class="split-word-inner">Poď na to</span></span>
            </h2>
            <p class="text-neutral-400 md:text-lg">Štyri kroky od registrácie po prvú zmenu v&nbsp;rozpise.</p>
        </div>

        {{-- One track runs through every icon's center - vertical on phones (steps stacked
             beside it), horizontal from `lg`. welcome.js measures the first and last icon to
             place it, then fills it as the section scrolls through the viewport and lights
             each step once the fill reaches it. --}}
        <div data-steps class="relative mx-auto max-w-md lg:max-w-6xl">
            <div data-steps-track class="pointer-events-none absolute bg-neutral-800" aria-hidden="true">
                <div data-steps-fill class="h-full w-full origin-top bg-brand-400 lg:origin-left"></div>
            </div>

            <ol class="grid grid-cols-1 gap-10 lg:grid-cols-4 lg:gap-8">
                @foreach($steps as $i => $step)
                    <li class="step relative flex items-start gap-5 lg:flex-col lg:items-center lg:text-center">
                        <div data-step-icon class="step-icon relative flex h-16 w-16 shrink-0 items-center justify-center rounded-full border-2 border-brand-500/60 bg-neutral-900 text-xl text-brand-400">
                            <i class="fa-solid {{ $step['icon'] }}" aria-hidden="true"></i>
                        </div>
                        <div class="step-copy pt-2 lg:pt-0">
                            <h3 class="mb-1.5 text-lg font-semibold text-neutral-100"><span class="text-neutral-400">{{ $i + 1 }}.</span> {{ $step['title'] }}</h3>
                            <p class="text-neutral-400 lg:mx-auto lg:max-w-[15rem]">{{ $step['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>

        <div class="reveal mx-auto mt-16 flex max-w-md flex-col gap-3 sm:flex-row lg:mt-20">
            <a href="{{ route('register') }}" class="cta-button flex min-h-[48px] w-full items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-transparent bg-neutral-100 px-6 py-3 font-semibold text-neutral-900 transition hover:bg-white focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-950">
                <i class="fa-solid fa-user-plus text-sm" aria-hidden="true"></i>
                Vytvoriť účet
            </a>
            <a href="{{ route('login') }}" class="cta-button flex min-h-[48px] w-full items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-neutral-700 bg-neutral-900 px-6 py-3 font-semibold text-neutral-100 transition hover:border-neutral-600 hover:bg-neutral-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-950">
                <i class="fa-solid fa-right-to-bracket text-sm" aria-hidden="true"></i>
                Prihlásiť sa
            </a>
        </div>

        <p class="mt-20 text-center text-sm text-neutral-400" aria-hidden="true">
            Kým sa rozhodneš, vyber si miesto v&nbsp;sále.
        </p>
    </div>

    {{-- Interactive auditorium, see resources/js/seat-field.js. Purely decorative, so it is
         hidden from assistive tech; the real actions are the buttons above. --}}
    <canvas data-seat-field class="mt-2 block h-[300px] w-full touch-pan-y sm:h-[380px] lg:h-[440px]" aria-hidden="true"></canvas>
</section>
