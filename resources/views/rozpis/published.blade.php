<x-layout :title="$title">
    {{-- The finished plan, read-only. Deliberately has no drag handles, no selects and no forms:
         everything here is text, so there is nothing to accidentally change. Mirrors the printed
         Excel sheet column for column - meno / pozícia / čas nástupu / náhradníci. --}}
    <div class="w-full px-4 py-6 sm:px-6 2xl:px-10">
        <div class="flex flex-col gap-5">

            <header class="flex flex-col gap-4 border-b border-neutral-800 pb-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="flex items-center gap-2.5 text-2xl font-bold text-white">
                        <i class="fa-solid fa-clipboard-list text-emerald-400"></i>
                        Rozpis zmien
                    </h1>

                    @if($publishedAt)
                        <p class="mt-1 flex items-center gap-1.5 text-xs text-neutral-400">
                            <i class="fa-solid fa-circle-check text-[0.7rem] text-emerald-500"></i>
                            Zverejnené {{ $publishedAt->format('d.m.Y') }} o {{ $publishedAt->format('H:i') }}. Toto je konečná verzia - len na čítanie.
                        </p>
                    @else
                        <p class="mt-1 flex items-center gap-1.5 text-xs text-amber-400">
                            <i class="fa-solid fa-pen-ruler text-[0.7rem]"></i>
                            Pracovná verzia - zamestnanci ju zatiaľ nevidia.
                        </p>
                    @endif
                </div>

                <div class="flex flex-wrap items-center justify-center gap-2">
                    <x-rozpis.week-arrow direction="previous"
                                        :href="route('rozpis.published', ['date' => $previousWeek->toDateString()])" />

                    <span class="inline-flex h-11 items-center gap-3 rounded-xl border border-neutral-800 bg-neutral-900 px-5 text-base font-bold tracking-wide text-neutral-100">
                        <i class="fa-regular fa-calendar text-neutral-400"></i>
                        <span class="whitespace-nowrap tabular-nums">{{ $weekStart->format('d.m.') }} - {{ $weekEnd->format('d.m.Y') }}</span>
                    </span>

                    <x-rozpis.week-arrow direction="next"
                                        :href="route('rozpis.published', ['date' => $nextWeek->toDateString()])" />

                    @if($canBuild)
                        <a href="{{ route('rozpis.export', ['date' => $weekStart->toDateString()]) }}"
                           class="ml-1 inline-flex h-11 items-center gap-2 rounded-xl border border-emerald-500/40 bg-emerald-500/10 px-4 text-sm font-semibold text-emerald-300 transition hover:bg-emerald-500/20">
                            <i class="fa-solid fa-file-excel"></i>
                            Excel
                        </a>

                        <a href="{{ route('rozpis.show', ['date' => $weekStart->toDateString()]) }}"
                           class="inline-flex h-11 items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-900 px-4 text-sm font-semibold text-neutral-300 transition hover:border-neutral-700 hover:text-white">
                            <i class="fa-solid fa-pen-to-square text-xs"></i>
                            Upraviť
                        </a>
                    @endif

                    <a href="{{ route('calendar.show', ['date' => $weekStart->toDateString()]) }}"
                       class="inline-flex h-11 items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-900 px-4 text-sm font-semibold text-neutral-300 transition hover:border-neutral-700 hover:text-white">
                        <i class="fa-solid fa-arrow-left text-xs"></i>
                        Kalendár
                    </a>
                </div>
            </header>

            @if($plan->every(fn (array $day): bool => $day['rows'] === []))
                <x-empty-state message="Na tento týždeň zatiaľ nie je zostavený žiadny rozpis." icon="fa-clipboard-question">
                    Keď vedúci rozpis zostaví a zverejní, uvidíte ho tu.
                </x-empty-state>
            @else
                <div class="grid grid-cols-1 items-start gap-3 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 2xl:gap-4">
                    @foreach($plan as $day)
                        {{-- One flag, reused for the card border and every row it touches. Standing
                             on an actual position only - a náhradník is signed up, not working,
                             so it does not light the day up. --}}
                        @php
                            $isMyRow = fn (array $row): bool => $row['user_id'] !== null && $row['user_id'] === auth()->id();
                            $isMyDay = collect($day['rows'])->contains($isMyRow)
                                || collect($day['managerRows'])->contains($isMyRow);
                        @endphp

                        <section id="day-{{ $day['date']->toDateString() }}" @class([
                            'flex flex-col overflow-hidden rounded-md border bg-neutral-900 shadow-sm',
                            'border-sky-500/40 ring-1 ring-sky-500/20' => $isMyDay,
                            'border-neutral-800' => ! $isMyDay,
                        ])>
                            <x-rozpis.day-heading :name="$day['dayName']" :date="$day['date']">
                                @if($day['manager'])
                                    <p class="mt-1 text-[0.7rem] text-neutral-400">
                                        manažér:
                                        <span @class([
                                            'font-semibold',
                                            'text-sky-300' => $isMyRow($day['managerRows'][0] ?? ['user_id' => null]),
                                            'text-neutral-300' => ! $isMyRow($day['managerRows'][0] ?? ['user_id' => null]),
                                        ])>{{ $day['manager'] }}</span>
                                    </p>
                                @endif

                                {{-- Manager slots are not counted - the vedúci is arranged apart
                                     from the rest and would otherwise flag every day. --}}
                                @if($day['unfilled'] > 0)
                                    <p class="mt-1.5 inline-flex items-center gap-1 rounded bg-amber-500/10 px-1.5 py-0.5 text-[0.65rem] font-semibold text-amber-400">
                                        <i class="fa-solid fa-triangle-exclamation text-[0.6rem]"></i>
                                        {{ $day['unfilled'] }}x neobsadené
                                    </p>
                                @endif
                            </x-rozpis.day-heading>

                            {{-- Separators are darker than the card, not lighter: a neutral-800 rule
                                 on a neutral-900 card reads as a bright line, and seven columns of
                                 them is a glare of white grid. Recessed, they only hint at the split. --}}
                            <div class="flex flex-col">
                                @forelse($day['rows'] as $row)
                                    @if($row['startsGroup'])
                                        <p class="bg-neutral-950/50 px-3 py-1 text-[0.6rem] font-bold uppercase tracking-widest text-neutral-500">
                                            <span class="truncate">{{ $row['group'] }}</span>
                                        </p>
                                    @endif

                                    {{-- No rule between people in a group. A line lighter than the
                                         card glares, a darker one still cuts the block up seven
                                         times over; a faint tint on every other row separates them
                                         without drawing an edge at all. --}}
                                    {{-- An unfilled row wins over the striping, and your own row
                                         wins over both - a gap in the plan and your own shift are
                                         the two things worth spotting in a column of names. --}}
                                    <div @class([
                                        'flex items-center justify-between gap-2 px-3 py-2',
                                        'bg-neutral-950/25' => $loop->index % 2 === 1 && $row['name'] !== null && ! $isMyRow($row),
                                        'bg-amber-500/[0.07]' => $row['name'] === null,
                                        'bg-sky-500/10' => $isMyRow($row),
                                    ])>
                                        <span class="min-w-0">
                                            <span @class([
                                                'block truncate text-sm font-semibold',
                                                'text-neutral-100' => $row['name'] !== null && ! $isMyRow($row),
                                                'text-amber-400/80 italic' => $row['name'] === null,
                                                'text-sky-300' => $isMyRow($row),
                                            ])>
                                                {{ $row['name'] ?? 'neobsadené' }}
                                            </span>
                                            <span class="block truncate text-[0.7rem] text-neutral-400">{{ $row['label'] }}</span>
                                        </span>

                                        @if($row['time'])
                                            <span class="shrink-0 rounded bg-neutral-800/80 px-1.5 py-0.5 text-[0.7rem] font-semibold tabular-nums text-neutral-300">
                                                {{ $row['time'] }}
                                            </span>
                                        @endif
                                    </div>
                                @empty
                                    <p class="px-3 py-3 text-center text-xs italic text-neutral-500">
                                        Žiadne pozície.
                                    </p>
                                @endforelse
                            </div>

                            @if($day['substitutes'])
                                <div class="border-t border-neutral-800/80 bg-neutral-900/60 px-3 py-2">
                                    <p class="text-[0.65rem] font-semibold uppercase tracking-wider text-neutral-500">
                                        Náhradníci
                                    </p>
                                    <p class="mt-1 text-xs leading-relaxed text-neutral-400">
                                        {{ implode(', ', $day['substitutes']) }}
                                    </p>
                                </div>
                            @endif

                            {{-- Manager-only: the signed-up, unplaced, non-absent pool in the order
                                 this day is decided on - who is owed an unpopular day when it is a
                                 hard one, who has earned the shift otherwise - in case an unfilled
                                 position gets filled by draw rather than by name.
                                 Everyone already sees these same people above as "Náhradníci"; this
                                 just adds the fairness order, which is advisory context a manager
                                 needs and a regular employee does not. --}}
                            @if($canBuild && $day['unfilled'] > 0 && $day['eligible'])
                                <div class="border-t border-neutral-800/80 bg-emerald-500/[0.04] px-3 py-2">
                                    <p class="text-[0.65rem] font-semibold uppercase tracking-wider text-emerald-500"
                                       title="Zapísaní, bez absencie tento deň, ešte nezaradení - zoradení podľa spravodlivosti: v neobľúbený deň je hore ten, kto ich odrobil najmenej, v obľúbený ten, kto si ho najviac zaslúžil.">
                                        Kto môže byť vylosovaný
                                    </p>
                                    <p class="mt-1 text-xs leading-relaxed text-neutral-400">
                                        @foreach($day['eligible'] as $person)
                                            {{ $person['name'] }} ({{ $person['score'] }}){{ ! $loop->last ? ', ' : '' }}
                                        @endforeach
                                    </p>
                                </div>
                            @endif
                        </section>
                    @endforeach
                </div>

                @php $todayDay = $plan->first(fn (array $day): bool => $day['date']->isToday()); @endphp
                @if($todayDay)
                    <script>
                        document.getElementById('day-{{ $todayDay['date']->toDateString() }}')
                            ?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                    </script>
                @endif
            @endif
        </div>
    </div>
</x-layout>
