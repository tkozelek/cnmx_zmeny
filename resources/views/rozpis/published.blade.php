<x-layout :title="$title">
    {{-- The finished plan, read-only. Deliberately has no drag handles, no selects and no forms:
         everything here is text, so there is nothing to accidentally change. Mirrors the printed
         Excel sheet column for column — meno / pozícia / čas nástupu / náhradníci. --}}
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
                            Zverejnené {{ $publishedAt->format('d.m.Y') }} o {{ $publishedAt->format('H:i') }}. Toto je konečná verzia — len na čítanie.
                        </p>
                    @else
                        <p class="mt-1 flex items-center gap-1.5 text-xs text-amber-400">
                            <i class="fa-solid fa-pen-ruler text-[0.7rem]"></i>
                            Pracovná verzia — zamestnanci ju zatiaľ nevidia.
                        </p>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a href="{{ route('rozpis.published', ['date' => $previousWeek->toDateString()]) }}"
                       title="Predchádzajúci týždeň"
                       class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-900 text-neutral-200 transition hover:border-neutral-700 hover:text-white">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>

                    <span class="inline-flex h-11 items-center gap-3 rounded-xl border border-neutral-800 bg-neutral-900 px-5 text-base font-bold tracking-wide text-neutral-100">
                        <i class="fa-regular fa-calendar text-neutral-400"></i>
                        <span class="whitespace-nowrap tabular-nums">{{ $weekStart->format('d.m.') }} – {{ $weekEnd->format('d.m.Y') }}</span>
                    </span>

                    <a href="{{ route('rozpis.published', ['date' => $nextWeek->toDateString()]) }}"
                       title="Nasledujúci týždeň"
                       class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-900 text-neutral-200 transition hover:border-neutral-700 hover:text-white">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>

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
                        <section class="flex flex-col overflow-hidden rounded-md border border-neutral-800 bg-neutral-900 shadow-sm">
                            <div class="flex flex-col items-center justify-center border-b border-neutral-800 bg-neutral-900/60 px-4 py-3 text-center">
                                <p class="truncate text-lg font-bold text-neutral-100">{{ $day['dayName'] }}</p>
                                <p class="mt-0.5 text-sm font-bold tracking-wide text-neutral-200">{{ $day['date']->format('d.m.Y') }}</p>

                                @if($day['manager'])
                                    <p class="mt-1 text-[0.7rem] text-neutral-400">
                                        manažér: <span class="font-semibold text-neutral-300">{{ $day['manager'] }}</span>
                                    </p>
                                @endif

                                {{-- Manager slots are not counted — the vedúci is arranged apart
                                     from the rest and would otherwise flag every day. --}}
                                @if($day['unfilled'] > 0)
                                    <p class="mt-1.5 inline-flex items-center gap-1 rounded bg-amber-500/10 px-1.5 py-0.5 text-[0.65rem] font-semibold text-amber-400">
                                        <i class="fa-solid fa-triangle-exclamation text-[0.6rem]"></i>
                                        {{ $day['unfilled'] }}x neobsadené
                                    </p>
                                @endif
                            </div>

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
                                    {{-- An unfilled row wins over the striping: a gap in the plan
                                         is the one thing worth spotting in a column of names. --}}
                                    <div @class([
                                        'flex items-center justify-between gap-2 px-3 py-2',
                                        'bg-neutral-950/25' => $loop->index % 2 === 1 && $row['name'] !== null,
                                        'bg-amber-500/[0.07]' => $row['name'] === null,
                                    ])>
                                        <span class="min-w-0">
                                            <span @class([
                                                'block truncate text-sm font-semibold',
                                                'text-neutral-100' => $row['name'] !== null,
                                                'text-amber-400/80 italic' => $row['name'] === null,
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
                        </section>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-layout>
