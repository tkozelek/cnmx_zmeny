<x-layout :title="$title">
    {{-- Desktop-first and deliberately full-bleed: seven columns of drop targets need the width,
         so this page opts out of the app's usual centred container. --}}
    <div class="w-full px-4 py-6 sm:px-6 2xl:px-10">
        <div class="flex flex-col gap-5">

            <header class="flex flex-col gap-4 border-b border-neutral-800 pb-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h1 class="flex items-center gap-2.5 text-2xl font-bold text-white">
                        <i class="fa-solid fa-table-list text-sky-400"></i>
                        Rozpis zmien

                        <button type="button" onclick="document.getElementById('rozpis-guide').showModal()"
                                title="Ako sa rozpis zostavuje a čo sa dá nastaviť"
                                class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-neutral-700 bg-neutral-900 text-sm text-neutral-400 transition hover:border-sky-500/50 hover:text-sky-300">
                            <i class="fa-solid fa-question text-xs"></i>
                        </button>
                    </h1>
                    <p class="mt-1 text-xs text-neutral-400">
                        Presuňte zapísaných zamestnancov na pozície. Pozície sa dajú preusporiadať uchopením
                        <i class="fa-solid fa-grip-vertical text-[0.65rem]"></i>.
                    </p>

                    {{-- The state of the week, spelled out. Whether the staff can already see this
                         plan is the one thing a manager must never have to guess at. --}}
                    @if($publishedAt)
                        <p class="mt-1.5 flex items-center gap-1.5 text-xs font-semibold text-emerald-400">
                            <i class="fa-solid fa-circle-check text-[0.7rem]"></i>
                            Zverejnené {{ $publishedAt->format('d.m.Y') }} o {{ $publishedAt->format('H:i') }} — zamestnanci rozpis vidia,
                            ďalšie úpravy sa prejavia okamžite.
                        </p>
                    @else
                        <p class="mt-1.5 flex items-center gap-1.5 text-xs font-semibold text-amber-400">
                            <i class="fa-solid fa-pen-ruler text-[0.7rem]"></i>
                            Pracovná verzia — zamestnanci ju zatiaľ nevidia.
                        </p>
                    @endif
                </div>

                {{-- Own week nav: x-date links to calendar.show, which would leave the builder. --}}
                <div class="flex items-center gap-2"
                     x-data="weekJump(@js($weekStart->toDateString()), @js(route('rozpis.show', ['date' => '__DATE__'])))">
                    <a href="{{ route('rozpis.show', ['date' => $previousWeek->toDateString()]) }}"
                       title="Predchádzajúci týždeň"
                       class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-900 text-neutral-200 transition hover:border-neutral-700 hover:text-white">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>

                    <button type="button" x-ref="trigger" @click="open()"
                            title="Kliknutím vyberte týždeň"
                            class="inline-flex h-11 items-center gap-3 rounded-xl border border-neutral-800 bg-neutral-900 px-5 text-base font-bold tracking-wide text-neutral-100 transition hover:border-neutral-700 hover:text-white">
                        <i class="fa-regular fa-calendar text-neutral-400"></i>
                        <span class="whitespace-nowrap tabular-nums">{{ $weekStart->format('d.m.') }} – {{ $weekEnd->format('d.m.Y') }}</span>
                        <i class="fa-solid fa-chevron-down text-xs text-neutral-500"></i>
                    </button>
                    <input x-ref="input" type="text" class="sr-only" tabindex="-1" aria-hidden="true">

                    <a href="{{ route('rozpis.show', ['date' => $nextWeek->toDateString()]) }}"
                       title="Nasledujúci týždeň"
                       class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-xl border border-neutral-800 bg-neutral-900 text-neutral-200 transition hover:border-neutral-700 hover:text-white">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>

                    <a href="{{ route('rozpis.export', ['date' => $weekStart->toDateString()]) }}"
                       title="Stiahnuť rozpis ako Excel na tlač"
                       class="ml-1 inline-flex h-11 items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-900 px-4 text-sm font-semibold text-neutral-300 transition hover:border-neutral-700 hover:text-white">
                        <i class="fa-solid fa-file-excel text-emerald-400"></i>
                        Excel
                    </a>

                    <a href="{{ route('rozpis.published', ['date' => $weekStart->toDateString()]) }}"
                       title="Pozrieť rozpis tak, ako ho vidia zamestnanci"
                       class="inline-flex h-11 items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-900 px-4 text-sm font-semibold text-neutral-300 transition hover:border-neutral-700 hover:text-white">
                        <i class="fa-solid fa-eye text-xs"></i>
                        Náhľad
                    </a>

                    {{-- Week-wide draft. Lives here rather than in a day card because the point of
                         it is the days seeing each other. --}}
                    <livewire:rozpis-week-ai :week-start="$weekStart->toDateString()" />

                    {{-- Publish / withdraw. The wording says what happens next, not what the row
                         is called: "zverejniť" is the promise that the staff will see it. --}}
                    <form method="POST" action="{{ route($publishedAt ? 'rozpis.unpublish' : 'rozpis.publish', ['date' => $weekStart->toDateString()]) }}">
                        @csrf
                        @if($publishedAt)
                            @method('DELETE')
                        @endif

                        <button type="submit"
                                @if($publishedAt) onclick="return confirm('Stiahnuť rozpis zo zverejnenia? Zamestnanci ho prestanú vidieť.')" @endif
                                @class([
                                    'inline-flex h-11 items-center gap-2 rounded-xl border px-4 text-sm font-semibold transition',
                                    'border-neutral-800 bg-neutral-900 text-neutral-300 hover:border-neutral-700 hover:text-white' => (bool) $publishedAt,
                                    'border-emerald-500/40 bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/20' => ! $publishedAt,
                                ])>
                            <i class="fa-solid {{ $publishedAt ? 'fa-eye-slash' : 'fa-bullhorn' }} text-xs"></i>
                            {{ $publishedAt ? 'Stiahnuť zo zverejnenia' : 'Zverejniť rozpis' }}
                        </button>
                    </form>

                    <a href="{{ route('calendar.show', ['date' => $weekStart->toDateString()]) }}"
                       title="Späť na zapisovanie"
                       class="inline-flex h-11 items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-900 px-4 text-sm font-semibold text-neutral-300 transition hover:border-neutral-700 hover:text-white">
                        <i class="fa-solid fa-arrow-left text-xs"></i>
                        Zapisovanie
                    </a>
                </div>
            </header>

            {{-- Advisory planning aid, read before the dragging starts. Collapsed by default and a
                 native <details>: seven drop columns need the width more than a table does, and a
                 disclosure widget is one tag against a component's worth of state. --}}
            @if($workload)
                <details class="group rounded-lg border border-neutral-800 bg-neutral-900">
                    <summary class="flex cursor-pointer list-none items-center gap-2 px-4 py-2.5 text-xs font-semibold text-neutral-300 transition hover:text-white">
                        <i class="fa-solid fa-chevron-right text-[0.6rem] text-neutral-500 transition-transform group-open:rotate-90"></i>
                        <i class="fa-solid fa-scale-balanced text-[0.7rem] text-sky-400"></i>
                        Odporúčané rozdelenie dní
                        <span class="font-normal text-neutral-500">— koľko zmien by mal tento týždeň dostať kto</span>
                    </summary>

                    <div class="overflow-x-auto border-t border-neutral-800/80 px-4 py-3">
                        <p class="mb-2.5 text-[0.7rem] leading-relaxed text-neutral-500">
                            Vychádza z toho, na koľko dní sa človek zapísal — kto je k dispozícii viac, dostane viac —
                            a z poradia spravodlivosti, ktoré rozdiel medzi rovnako dostupnými ľuďmi nakloní
                            v prospech toho, kto je viac na ťahu. Nikdy neodporučí viac dní, než na koľko sa zapísal.
                            Je to odporúčanie, nie pravidlo.
                        </p>

                        <table class="w-full min-w-[28rem] text-xs">
                            <thead>
                                <tr class="text-left text-[0.65rem] uppercase tracking-wider text-neutral-500">
                                    <th class="pb-1.5 pr-3 font-semibold">Meno</th>
                                    <th class="pb-1.5 pr-3 text-center font-semibold">Zapísaný</th>
                                    <th class="pb-1.5 pr-3 text-center font-semibold">Odporúčame</th>
                                    <th class="pb-1.5 pr-3 text-center font-semibold">Zaradený</th>
                                    <th class="pb-1.5 text-center font-semibold">Poradie</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($workload as $row)
                                    <tr @class(['border-t border-neutral-800/60', 'bg-neutral-950/25' => $loop->index % 2 === 1])>
                                        <td class="py-1.5 pr-3 font-semibold text-neutral-100">{{ $row['name'] }}</td>
                                        <td class="py-1.5 pr-3 text-center tabular-nums text-neutral-400">{{ $row['signups'] }}</td>
                                        <td class="py-1.5 pr-3 text-center font-bold tabular-nums text-sky-300">{{ $row['target'] }}</td>
                                        {{-- Under target is the actionable state; the count is from
                                             page load, so reload after a round of dragging. --}}
                                        <td @class([
                                            'py-1.5 pr-3 text-center font-semibold tabular-nums',
                                            'text-amber-400' => $row['placed'] < $row['target'],
                                            'text-neutral-400' => $row['placed'] >= $row['target'],
                                        ])>{{ $row['placed'] }}</td>
                                        <td class="py-1.5 text-center tabular-nums text-neutral-500">{{ number_format($row['priorityScore'], 1) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </details>
            @endif

            {{-- Edit history for this week. Same collapsed treatment as the workload table: needed
                 when something looks wrong, in the way the rest of the time. --}}
            @if($history->isNotEmpty())
                <details class="group rounded-lg border border-neutral-800 bg-neutral-900">
                    <summary class="flex cursor-pointer list-none items-center gap-2 px-4 py-2.5 text-xs font-semibold text-neutral-300 transition hover:text-white">
                        <i class="fa-solid fa-chevron-right text-[0.6rem] text-neutral-500 transition-transform group-open:rotate-90"></i>
                        <i class="fa-solid fa-clock-rotate-left text-[0.7rem] text-sky-400"></i>
                        História zmien
                        <span class="font-normal text-neutral-500">— kto čo v tomto týždni zmenil</span>
                    </summary>

                    <div class="max-h-72 overflow-y-auto border-t border-neutral-800/80 px-4 py-3">
                        <ul class="flex flex-col">
                            @foreach($history as $entry)
                                @php
                                    $what = class_basename($entry->subject_type) === 'Assignment' ? 'zaradenie' : 'pozíciu v dni';
                                    $verb = ['created' => 'pridal', 'updated' => 'upravil', 'deleted' => 'zmazal'][$entry->event] ?? $entry->event;
                                @endphp

                                <li @class([
                                    'flex flex-wrap items-baseline gap-x-1.5 gap-y-0.5 px-1.5 py-1.5 text-xs',
                                    'bg-neutral-950/25' => $loop->index % 2 === 1,
                                ])>
                                    <span class="font-semibold text-neutral-200">{{ $entry->causer ?? 'Systém' }}</span>
                                    <span class="text-neutral-400">{{ $verb }} {{ $what }}</span>

                                    @if($day = data_get($entry->properties, 'date'))
                                        <span class="text-neutral-500">({{ \Carbon\CarbonImmutable::parse($day)->format('d.m.') }})</span>
                                    @endif

                                    <span class="ml-auto shrink-0 tabular-nums text-[0.7rem] text-neutral-600">
                                        {{ $entry->created_at->format('d.m. H:i') }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </details>
            @endif

            @if($positions->isEmpty())
                <x-empty-state message="Kino nemá zatiaľ žiadne aktívne pozície." icon="fa-list-check">
                    Najprv si <a href="{{ route('positions.index') }}" class="font-semibold text-sky-400 hover:underline">definujte pozície</a>, potom sa dá zostaviť rozpis.
                </x-empty-state>
            @else
                {{-- Seven across from lg up: the full-width shell above is what makes the columns
                     wide enough to drop into comfortably. --}}
                <div class="grid grid-cols-1 items-start gap-3 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 2xl:gap-4">
                    @foreach($days as $day)
                        <livewire:rozpis-day :date="$day->toDateString()"
                                             :initial-slots="$weekSlots->get($day->toDateString(), collect())"
                                             :initial-assignments="$weekAssignments->get($day->toDateString(), collect())"
                                             :initial-fairness="$fairness"
                                             :key="'rozpis-'.$day->toDateString()" />
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @include('rozpis._guide')
</x-layout>
