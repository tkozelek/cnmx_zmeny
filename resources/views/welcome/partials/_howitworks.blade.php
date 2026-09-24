@php
    $steps = [
        ['icon' => 'fa-user-plus', 'title' => '1. Založíš si účet', 'text' => 'Vyplníš krátky formulár - meno, e-mail, heslo.'],
        ['icon' => 'fa-envelope-circle-check', 'title' => '2. Potvrdíš e-mail', 'text' => 'Klikneš na odkaz, ktorý ti pošleme, aby sme vedeli, že účet je tvoj.'],
        ['icon' => 'fa-user-check', 'title' => '3. Počkáš na schválenie', 'text' => 'Manažér kina ti účet potvrdí, potom sa už len prihlásiš.'],
        ['icon' => 'fa-calendar-days', 'title' => '4. Zapíšeš si zmeny', 'text' => 'Označíš dni, kedy môžeš pracovať, a sleduješ, ako to manažér rozpísal.'],
    ];
@endphp

<section id="ako-to-funguje" class="bg-neutral-950 py-20 sm:py-28">
    <div class="container mx-auto px-4 text-center">
        <div class="reveal">
            <h2 class="mb-2 text-3xl font-bold text-neutral-100 md:text-4xl">Ako to funguje</h2>
            <p class="mx-auto mb-16 max-w-2xl text-neutral-400">Štyri kroky od registrácie po prvú zmenu v rozpise.</p>
        </div>

        {{-- Fixed-width connector tracks (not `auto`) so the line divs - which have no
             intrinsic content width of their own - get a predictable, equal gap instead
             of collapsing. Row layout waits for `lg` since four columns need more room
             than three did. --}}
        <div class="grid grid-cols-1 gap-y-12 lg:grid-cols-[1fr_3rem_1fr_3rem_1fr_3rem_1fr] lg:items-start lg:gap-x-2">
            @foreach($steps as $i => $step)
                <div class="reveal relative flex flex-col items-center px-2" style="transition-delay: {{ $i * 120 }}ms;">
                    <div class="relative mb-4 flex h-20 w-20 items-center justify-center rounded-full border-2 border-brand-500/60 bg-neutral-900 text-2xl text-brand-400 shadow-lg shadow-brand-950/20">
                        <i class="fa-solid {{ $step['icon'] }}"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-neutral-100">{{ $step['title'] }}</h3>
                    <p class="max-w-[15rem] text-neutral-400">{{ $step['text'] }}</p>
                </div>

                @if(! $loop->last)
                    {{-- Draws in once its step has revealed - a static line would just sit
                         there; tying it to the same .active toggle makes the flow feel led. --}}
                    <div class="reveal-line hidden h-0.5 w-full self-center bg-brand-500/30 lg:block" style="transition-delay: {{ $i * 120 + 200 }}ms; margin-top: 2.5rem;"></div>
                @endif
            @endforeach
        </div>
    </div>
</section>
