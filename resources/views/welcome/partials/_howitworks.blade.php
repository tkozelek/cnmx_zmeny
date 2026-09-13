@php
    $steps = [
        ['icon' => 'fa-user-plus', 'title' => '1. Vytvorenie účtu', 'text' => 'Rýchlo sa zaregistrujte a získajte prístup k plánovaciemu kalendáru vášho kina.'],
        ['icon' => 'fa-envelope-circle-check', 'title' => '2. Overenie e-mailu', 'text' => 'Potvrďte odkaz, ktorý vám pošleme na e-mail - overíme tak, že účet je naozaj váš.'],
        ['icon' => 'fa-user-check', 'title' => '3. Schválenie registrácie', 'text' => 'Počkajte, kým vám manažér overí účet. Potom sa môžete prihlásiť.'],
        ['icon' => 'fa-calendar-days', 'title' => '4. Zapisovanie zmien', 'text' => 'Zapíšte si, ktoré dni ste k dispozícii, a počkajte na finálny rozpis.'],
    ];
@endphp

<section id="ako-to-funguje" class="bg-neutral-950 py-20 sm:py-28">
    <div class="container mx-auto px-4 text-center">
        <div class="reveal">
            <h2 class="mb-2 text-3xl font-bold text-neutral-100 md:text-4xl">Ako to funguje?</h2>
            <p class="mx-auto mb-16 max-w-2xl text-neutral-400">Vo štyroch jednoduchých krokoch si naplánujete svoju dostupnosť.</p>
        </div>

        {{-- Fixed-width connector tracks (not `auto`) so the line divs - which have no
             intrinsic content width of their own - get a predictable, equal gap instead
             of collapsing. Row layout waits for `lg` since four columns need more room
             than three did. --}}
        <div class="grid grid-cols-1 gap-y-12 lg:grid-cols-[1fr_3rem_1fr_3rem_1fr_3rem_1fr] lg:items-start lg:gap-x-2">
            @foreach($steps as $i => $step)
                <div class="reveal relative flex flex-col items-center px-2" style="transition-delay: {{ $i * 120 }}ms;">
                    <span class="pointer-events-none absolute -top-3 select-none text-6xl font-black text-neutral-900" aria-hidden="true">
                        0{{ $i + 1 }}
                    </span>
                    <div class="relative mb-4 flex h-20 w-20 items-center justify-center rounded-full border-2 border-sky-500/40 bg-neutral-900 text-2xl text-sky-400 shadow-lg shadow-sky-950/20">
                        <i class="fa-solid {{ $step['icon'] }}"></i>
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-neutral-100">{{ $step['title'] }}</h3>
                    <p class="max-w-[15rem] text-neutral-400">{{ $step['text'] }}</p>
                </div>

                @if(! $loop->last)
                    {{-- Draws in once its step has revealed - a static line would just sit
                         there; tying it to the same .active toggle makes the flow feel led. --}}
                    <div class="reveal-line hidden h-0.5 w-full self-center bg-gradient-to-r from-sky-500/50 to-sky-500/10 lg:block" style="transition-delay: {{ $i * 120 + 200 }}ms; margin-top: 2.5rem;"></div>
                @endif
            @endforeach
        </div>
    </div>
</section>
