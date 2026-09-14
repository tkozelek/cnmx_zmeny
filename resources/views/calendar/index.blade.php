<x-layout :title="$title" description="Zapíšte sa na pracovné zmeny v aktuálnom týždni.">
    <div class="container mx-auto px-4 py-6 md:px-6 lg:px-8">
        <div class="flex flex-col gap-6">

            {{-- Centered Header & Week Selector --}}
            <header class="flex flex-col items-center justify-center gap-3 text-center">
                <x-date :week-start="$weekStart" :week-end="$weekEnd" :previous="$previousWeek" :next="$nextWeek" :locked="$locked" :locked-week-starts="$lockedWeekStarts ?? []" />

                {{-- Action Buttons Under Week Selector --}}
                <div class="flex flex-wrap items-center justify-center gap-3 pt-1">
                    @can('lock', \App\Models\Assignment::class)
                        <form method="POST" action="{{ route($locked ? 'weeks.unlock' : 'weeks.lock', ['date' => $weekStart->toDateString()]) }}">
                            @csrf
                            @if($locked)
                                @method('DELETE')
                            @endif
                            <button type="submit" @class([
                                'inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-neutral-950 shadow-sm',
                                'bg-amber-600 hover:bg-amber-500 text-white border-b-2 border-amber-700 focus:ring-amber-400' => $locked,
                                'bg-rose-600 hover:bg-rose-500 text-white border-b-2 border-rose-700 hover:text-white focus:ring-rose-500' => ! $locked,
                            ])>
                                <i class="fa-solid {{ $locked ? 'fa-lock-open text-amber-200' : 'fa-lock text-rose-200' }}"></i>
                                {{ $locked ? 'Odomknúť týždeň' : 'Zamknúť týždeň' }}
                            </button>
                        </form>

                        {{-- Only once the week is frozen: the builder needs a settled signup list. --}}
                        @if($locked)
                            <a href="{{ route('rozpis.show', ['date' => $weekStart->toDateString()]) }}"
                               class="inline-flex min-h-10 items-center gap-2 rounded-md border-b-2 border-indigo-700 bg-indigo-600 hover:bg-indigo-500 px-4 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2 focus:ring-offset-neutral-950">
                                <i class="fa-solid fa-table-list text-indigo-200"></i>
                                Rozpis
                            </a>
                        @endif

                        <a href="{{ route('schedule.export', ['date' => $weekStart->toDateString()]) }}"
                           class="inline-flex min-h-10 items-center gap-2 rounded-md border-b-2 border-emerald-800 bg-emerald-700 hover:bg-emerald-600 px-4 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-neutral-950">
                            <i class="fa-solid fa-file-arrow-down text-emerald-200"></i>
                            Excel
                        </a>
                    @endcan

                    {{-- Everyone's way into the finished plan. Only offered once it is published:
                         a link that bounces with "not published yet" is worse than no link. --}}
                    @if($rozpisPublished)
                        <a href="{{ route('rozpis.published', ['date' => $weekStart->toDateString()]) }}"
                           class="inline-flex min-h-10 items-center gap-2 rounded-md border-b-2 border-emerald-700 bg-emerald-600 hover:bg-emerald-500 px-4 text-sm font-semibold text-white shadow-sm transition focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:ring-offset-2 focus:ring-offset-neutral-950">
                            <i class="fa-solid fa-clipboard-list text-emerald-200"></i>
                            Rozpis zmien
                        </a>
                    @endif

                    @include('partials._fileuploadmodal')
                </div>
            </header>

            {{-- Controls row: Names toggle on left, Extra info note on right --}}
            <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-3 pt-2">
                <x-names-toggle />
                <livewire:extra-note />
            </div>

            {{-- Day cards grid (items-start so columns don't stretch to uniform height) --}}
            <div class="grid grid-cols-1 items-start gap-4 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7">
                @foreach($days as $day)
                    <livewire:day-card :date="$day->toDateString()"
                                       :locked="$locked"
                                       :initial-assignments="$weekAssignments->get($day->toDateString(), collect())"
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

                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-neutral-800 text-neutral-300 border border-neutral-700 shrink-0">
                                        {{ $absences->count() }} {{ $absences->count() === 1 ? 'absencia' : ($absences->count() < 5 ? 'absencie' : 'absencií') }}
                                    </span>
                                </div>

                                {{-- Scrollable Table with Sticky Header --}}
                                <div class="relative max-h-[380px] overflow-y-auto custom-scrollbar">
                                    <table class="w-full text-left text-sm text-neutral-300">
                                        <thead class="sticky top-0 z-10 border-b border-neutral-800 bg-neutral-950 text-xs font-medium uppercase tracking-wider text-neutral-400">
                                            <tr>
                                                <th class="px-4 py-2">Zamestnanec</th>
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
                                                                <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold uppercase tracking-wider bg-neutral-700 text-neutral-200">Ja</span>
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
                                                                <span class="inline-flex items-center px-1.5 py-0.2 rounded text-[10px] font-medium text-amber-300 bg-neutral-800 border border-neutral-700 ml-1">trvalá</span>
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
