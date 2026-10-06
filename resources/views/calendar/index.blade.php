<x-layout :title="$title" description="Zapíšte sa na pracovné zmeny v aktuálnom týždni.">
    <div class="container mx-auto px-4 py-6 md:px-6 lg:px-8">
        <div class="flex flex-col gap-6">

            {{-- Centered Header & Week Selector --}}
            <header class="flex flex-col items-center justify-center gap-3 text-center">
                <h1 class="sr-only">Týždeň {{ $weekStart->format('d.m.') }} – {{ $weekEnd->format('d.m.Y') }}</h1>

                <x-date :week-start="$weekStart" :week-end="$weekEnd" :previous="$previousWeek" :next="$nextWeek" :locked="$locked" :locked-week-starts="$lockedWeekStarts ?? []" />

                @if($locked)
                    @cannot('lock', \App\Models\Assignment::class)
                        <p class="text-sm text-neutral-300"><i class="fa-solid fa-lock mr-1.5 text-xs text-rose-400" aria-hidden="true"></i>Týždeň je zamknutý – zápisy sa už nedajú meniť.</p>
                    @endcannot
                @endif

                {{-- Action Buttons Under Week Selector --}}
                @can('lock', \App\Models\Assignment::class)
                    <div class="flex flex-wrap items-center justify-center gap-3 pt-1">
                        <form method="POST" action="{{ route($locked ? 'weeks.unlock' : 'weeks.lock', ['date' => $weekStart->toDateString()]) }}">
                            @csrf
                            @if($locked)
                                @method('DELETE')
                            @endif
                            <button type="submit" @class([
                                'inline-flex min-h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold text-white transition',
                                'bg-brand-700 hover:bg-brand-800' => $locked,
                                'bg-rose-700 hover:bg-rose-800' => ! $locked,
                            ])>
                                <i class="fa-solid {{ $locked ? 'fa-lock-open' : 'fa-lock' }}" aria-hidden="true"></i>
                                {{ $locked ? 'Odomknúť týždeň' : 'Zamknúť týždeň' }}
                            </button>
                        </form>

                        <a href="{{ route('schedule.export', ['date' => $weekStart->toDateString()]) }}"
                           class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-emerald-700 px-4 text-sm font-semibold text-white transition hover:bg-emerald-800">
                            <i class="fa-solid fa-file-arrow-down" aria-hidden="true"></i>
                            Excel
                        </a>
                    </div>
                @endcan
            </header>

            {{-- Controls row: toggles on the left, the shared signup note on the right --}}
            <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 pt-2">
                <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                    <x-names-toggle />
                    @can('lock', \App\Models\Assignment::class)
                        <x-names-toggle id="draw_checkbox" label="Zobraziť voľných na losovanie" :checked="false" />
                    @endcan
                </div>
                <livewire:extra-note />
            </div>

            {{-- Day cards grid (items-start so columns don't stretch to uniform height) --}}
            <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                @foreach($days as $day)
                    <livewire:day-card :date="$day->toDateString()"
                                       :locked="$locked"
                                       :initial-assignments="$weekAssignments->get($day->toDateString(), collect())"
                                       :available="$available[$day->toDateString()] ?? []"
                                       :key="'day-'.$day->toDateString()" />
                @endforeach
            </div>

            {{-- Bottom Insights Section (Signup Summary & Absences) --}}
            @php
                $canViewSummary = auth()->user()?->hasPermissionInTeam('user.view-any', app(\App\Models\Team::class));
                $hasAbsences = $absences->isNotEmpty();
            @endphp

            @if($canViewSummary || $hasAbsences)
                <div class="grid grid-cols-1 {{ ($canViewSummary && $hasAbsences) ? 'lg:grid-cols-12' : '' }} gap-6 pt-3 items-start">
                    @if($canViewSummary)
                        <div class="{{ $hasAbsences ? 'lg:col-span-5 xl:col-span-5' : 'w-full max-w-2xl' }}">
                            <livewire:signup-summary :week-start="$weekStart->toDateString()" :key="'summary-'.$weekStart->toDateString()" />
                        </div>
                    @endif

                    @if($hasAbsences)
                        <div class="{{ $canViewSummary ? 'lg:col-span-7 xl:col-span-7' : 'w-full' }}">
                            <section class="rounded-lg border border-neutral-800 bg-neutral-900 shadow-sm overflow-hidden flex flex-col">
                                {{-- Card Header --}}
                                <div class="px-4 py-3 border-b border-neutral-800 bg-neutral-950 flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <i class="fa-solid fa-calendar-xmark text-neutral-400 text-xs shrink-0"></i>
                                        <div>
                                            <h2 class="text-sm font-semibold text-neutral-100 truncate">Absencie v tomto týždni</h2>
                                        </div>
                                    </div>

                                    <span class="text-xs text-neutral-400 shrink-0">
                                        {{ $absences->count() }} {{ $absences->count() === 1 ? 'absencia' : ($absences->count() < 5 ? 'absencie' : 'absencií') }}
                                    </span>
                                </div>

                                {{-- Scrollable Table with Sticky Header --}}
                                <div class="relative lg:max-h-[380px] lg:overflow-y-auto custom-scrollbar">
                                    <table class="w-full text-left text-sm text-neutral-300">
                                        <thead class="sticky top-0 z-10 border-b border-neutral-800 bg-neutral-950 text-xs font-medium uppercase tracking-wider text-neutral-400">
                                            <tr>
                                                <th class="px-4 py-2">Meno</th>
                                                <th class="px-4 py-2">Trvanie</th>
                                                <th class="px-4 py-2 hidden sm:table-cell">Dôvod</th>
                                                <th class="px-4 py-2 text-right hidden md:table-cell">Nahlásené</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-neutral-800">
                                            @foreach($absences as $absence)
                                                @php
                                                    $isOwn = auth()->id() === $absence->user_id;
                                                @endphp
                                                <tr @class([
                                                    'transition-colors duration-150',
                                                    'bg-neutral-800/40' => $isOwn,
                                                    'hover:bg-neutral-800/60' => true,
                                                ])>
                                                    <td class="px-4 py-2.5 min-w-0">
                                                        <div class="flex items-center gap-2">
                                                            <span class="truncate text-sm {{ $isOwn ? 'text-white font-semibold' : 'text-neutral-200' }}">
                                                                {{ $absence->user }}
                                                            </span>
                                                            @if($isOwn)
                                                                <span class="text-xs text-neutral-400">(ja)</span>
                                                            @endif
                                                        </div>
                                                        @if($absence->reason)
                                                            <p class="sm:hidden text-xs text-neutral-400 mt-0.5 truncate">
                                                                {{ $absence->reason }}
                                                            </p>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2.5 whitespace-nowrap">
                                                        <span class="text-xs text-neutral-200 font-medium">
                                                            {{ $absence->date_from->format('d.m.') }}
                                                            @if($absence->isOpenEnded())
                                                                <span class="ml-1 text-neutral-400">– trvalá</span>
                                                            @elseif($absence->date_to && ! $absence->date_to->equalTo($absence->date_from))
                                                                – {{ $absence->date_to->format('d.m.') }}
                                                            @endif
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-2.5 hidden sm:table-cell max-w-xs">
                                                        @if($absence->reason)
                                                            <span class="text-xs text-neutral-300 truncate block" title="{{ $absence->reason }}">
                                                                {{ $absence->reason }}
                                                            </span>
                                                        @else
                                                            <span class="text-xs text-neutral-400">–</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2.5 text-right whitespace-nowrap hidden md:table-cell text-xs text-neutral-400">
                                                        {{ $absence->created_at->format('d.m. H:i') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</x-layout>
