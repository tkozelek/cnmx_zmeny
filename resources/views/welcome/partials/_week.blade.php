@php
    // A sample of the real calendar's next week (default team week starts on Thursday), drawn
    // with the same day-card language. `mine` = the visitor signed up, `others` = teammates.
    $weekStart = now()->toImmutable()->locale('sk')->next(\Carbon\CarbonInterface::THURSDAY);
    $weekEnd = $weekStart->addDays(6);
    $sampleDays = [
        ['mine' => true, 'others' => ['Lucia K.', 'Matej P.']],
        ['mine' => false, 'others' => ['Simona D.', 'Adam R.', 'Nina V.']],
        ['mine' => true, 'others' => ['Adam R.']],
        ['mine' => true, 'others' => ['Lucia K.', 'Peter H.']],
        ['mine' => false, 'others' => ['Matej P.']],
        ['mine' => false, 'others' => ['Nina V.']],
        ['mine' => false, 'others' => ['Simona D.', 'Peter H.']],
    ];
    $absenceFrom = $weekStart->addDays(5);

    $sceneSteps = [
        ['title' => 'Vyberieš si dni', 'text' => 'Pri dni, kedy môžeš, ťukneš na „Zapísať sa“. Zmeníš plány? Odpíšeš sa rovnako rýchlo.'],
        ['title' => 'Vidíš, kto ide s tebou', 'text' => 'Pri každom dni sú mená kolegov, ktorí sú zapísaní. Celý tím vidí ten istý týždeň.'],
        ['title' => 'Absenciu nahlásiš vopred', 'text' => 'Dovolenku či skúškové zapíšeš raz a manažér ju uvidí priamo pri týždni.'],
        ['title' => 'Týždeň sa zamkne', 'text' => 'Keď je rozpis hotový, manažér týždeň zamkne. Zápisy potom platia a nič sa už nepresúva.'],
    ];
@endphp

<section id="ako-to-funguje" class="bg-neutral-950 pt-24 sm:pt-32">
    <div class="container mx-auto px-4">
        <div class="reveal mx-auto mb-12 max-w-2xl text-center lg:mb-4">
            <h2 class="mb-3 text-3xl font-bold text-neutral-100 md:text-5xl">
                <span class="split-word"><span class="split-word-inner">Tvoj týždeň v kine</span></span>
            </h2>
            <p class="text-neutral-400 md:text-lg">Takto vyzerá rozpis, keď sa prihlásiš.</p>
        </div>

        {{-- Sticky scene, see mountWeekScene() in welcome.js: the board holds still while the
             steps scroll past it, and each step switches it to its state. Without JS, with
             reduced motion or on a short screen it is a plain board in its final state above the list. --}}
        <div data-week-scene class="week-scene mx-auto grid max-w-6xl grid-cols-1 gap-x-16 lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
            <div class="week-scene__stage lg:order-2">
                <div data-week-board class="week-board overflow-hidden rounded-xl border border-neutral-800 bg-neutral-900 shadow-2xl shadow-black/40" aria-hidden="true">
                    <div class="flex items-center justify-between gap-3 border-b border-neutral-800 bg-neutral-950 px-4 py-3">
                        <p class="text-sm font-semibold text-neutral-100">
                            Týždeň {{ $weekStart->format('d.m.') }} – {{ $weekEnd->format('d.m.Y') }}
                        </p>
                        <p data-from="4" class="week-pop flex items-center gap-1.5 text-xs font-medium text-neutral-300">
                            <i class="fa-solid fa-lock text-[0.65rem] text-neutral-400"></i>
                            Zamknutý
                        </p>
                    </div>

                    <ul>
                        @foreach($sampleDays as $i => $day)
                            @php($date = $weekStart->addDays($i))
                            <li class="grid grid-cols-[4.75rem_minmax(0,1fr)_6.25rem] items-center gap-3 border-b border-neutral-800/70 px-3 py-1.5 sm:grid-cols-[5.5rem_minmax(0,1fr)_7rem] sm:px-4 sm:py-2.5" style="--row: {{ $i }}">
                                <div class="min-w-0">
                                    <p class="truncate text-[0.8125rem] font-bold leading-tight text-neutral-100 sm:text-sm">{{ Str::title($date->dayName) }}</p>
                                    <p class="text-[0.6875rem] font-semibold text-neutral-400 sm:text-xs">{{ $date->format('d.m.') }}</p>
                                </div>

                                <p class="flex min-w-0 flex-wrap gap-x-2 gap-y-0.5 text-xs leading-tight sm:text-sm sm:leading-snug">
                                    @if($day['mine'])
                                        <span data-from="1" class="week-pop font-bold text-emerald-400">Ty</span>
                                    @endif
                                    @foreach($day['others'] as $name)
                                        <span data-from="2" class="week-pop whitespace-nowrap text-neutral-300">{{ $name }}</span>
                                    @endforeach
                                </p>

                                {{-- Stacked states: each later layer covers the one before it. --}}
                                <div class="grid text-xs font-semibold text-white sm:text-sm">
                                    <span class="week-btn bg-brand-700">Zapísať sa</span>
                                    @if($day['mine'])
                                        <span data-from="1" class="week-btn week-swap bg-emerald-700">Zapísaný</span>
                                        <span data-from="4" class="week-btn week-swap gap-1.5 bg-emerald-700">Zapísaný <i class="fa-solid fa-lock text-[0.6rem] text-emerald-100"></i></span>
                                    @else
                                        <span data-from="4" class="week-btn week-swap border border-neutral-800 bg-neutral-950 font-medium text-neutral-400">Zamknutý</span>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <div class="grid">
                        <p class="flex items-center px-4 py-3 text-xs text-neutral-400 [grid-area:1/1]">Žiadne absencie v tomto týždni</p>
                        <div data-from="3" class="week-swap flex items-center justify-between gap-3 bg-neutral-900 px-4 py-3 text-xs [grid-area:1/1]">
                            <span class="flex items-center gap-2 font-semibold text-neutral-100">
                                <i class="fa-solid fa-calendar-xmark text-neutral-400"></i>
                                Absencia
                            </span>
                            <span class="text-neutral-300"><span class="font-semibold text-white">Ty</span> · {{ $absenceFrom->format('d.m.') }} – {{ $weekEnd->format('d.m.') }} · skúškové</span>
                        </div>
                    </div>
                </div>

                {{-- Phones only: the board covers the scrolling steps, so the active one is repeated here. --}}
                <div data-scene-caption class="week-scene__caption" aria-hidden="true">
                    <p data-scene-caption-title class="font-semibold text-neutral-100"></p>
                    <p data-scene-caption-text class="mt-1 text-sm text-neutral-300"></p>
                </div>
            </div>

            <ol class="week-scene__steps lg:order-1">
                @foreach($sceneSteps as $i => $step)
                    <li data-scene-step="{{ $i + 1 }}" class="scene-step flex gap-5 py-8">
                        <span class="scene-step__marker flex h-9 w-9 shrink-0 items-center justify-center rounded-full border text-sm font-bold">{{ $i + 1 }}</span>
                        <div>
                            <h3 class="scene-step__title mb-2 text-xl font-semibold md:text-2xl">{{ $step['title'] }}</h3>
                            <p class="max-w-md text-neutral-400 md:text-lg">{{ $step['text'] }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</section>
