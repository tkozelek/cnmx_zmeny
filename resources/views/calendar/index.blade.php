<x-layout :title="$title">
    <div class="container mx-auto px-4 py-6 md:px-6 lg:px-8">
        <div class="flex flex-col gap-6">

            {{-- Centered Header & Week Selector --}}
            <header class="flex flex-col items-center justify-center gap-3 text-center">
                <x-date :week-start="$weekStart" :week-end="$weekEnd" :previous="$previousWeek" :next="$nextWeek" :locked="$locked" :locked-week-starts="$lockedWeekStarts ?? []" />

                {{-- Action Buttons Under Week Selector --}}
                <div class="flex flex-wrap items-center justify-center gap-3 pt-1">
                    @if(auth()->user()->hasRole('admin'))
                        <form method="POST" action="{{ route($locked ? 'weeks.unlock' : 'weeks.lock', ['date' => $weekStart->toDateString()]) }}">
                            @csrf
                            @if($locked)
                                @method('DELETE')
                            @endif
                            <button type="submit" @class([
                                'inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-medium transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-neutral-950',
                                'bg-sky-500/10 text-sky-300 border border-sky-500/40 hover:bg-sky-500/20 focus:ring-sky-400' => $locked,
                                'bg-neutral-900 text-neutral-300 ring-1 ring-inset ring-neutral-800 hover:bg-neutral-800 hover:text-white focus:ring-neutral-500' => ! $locked,
                            ])>
                                <i class="fa-solid {{ $locked ? 'fa-lock-open text-sky-400' : 'fa-lock' }}"></i>
                                {{ $locked ? 'Odomknúť týždeň' : 'Zamknúť týždeň' }}
                            </button>
                        </form>

                        <a href="{{ route('schedule.export', ['date' => $weekStart->toDateString()]) }}"
                           class="inline-flex min-h-10 items-center gap-2 rounded-md px-4 text-sm font-medium bg-neutral-900 text-neutral-300 ring-1 ring-inset ring-neutral-800 hover:bg-neutral-800 hover:text-white focus:ring-neutral-500 transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-neutral-950">
                            <i class="fa-solid fa-file-arrow-down"></i>
                            Excel
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

            {{-- Live updating signup summary table --}}
            <livewire:signup-summary :week-start="$weekStart->toDateString()" :key="'summary-'.$weekStart->toDateString()" />

            @if($absences->isNotEmpty())
                <section class="flex flex-col gap-3">
                    <h2 class="text-lg font-semibold text-neutral-100">Absencie v tomto týždni</h2>
                    <x-table :headers="['Meno', 'Začiatok', 'Koniec', 'Nahlásené', 'Dôvod']">
                        @foreach($absences as $absence)
                            <x-table-row>
                                <x-table-cell class="font-medium text-neutral-100">{{ $absence->user }}</x-table-cell>
                                <x-table-cell class="whitespace-nowrap text-neutral-300">{{ $absence->date_from->format('d.m.') }}</x-table-cell>
                                <x-table-cell class="whitespace-nowrap text-neutral-300">
                                    {{ $absence->isOpenEnded() ? 'trvalá' : $absence->date_to->format('d.m.') }}
                                </x-table-cell>
                                <x-table-cell class="whitespace-nowrap text-neutral-500">{{ $absence->created_at->format('d.m. H:i') }}</x-table-cell>
                                <x-table-cell class="text-neutral-300">{{ $absence->reason ?: '—' }}</x-table-cell>
                            </x-table-row>
                        @endforeach
                    </x-table>
                </section>
            @endif
        </div>
    </div>
</x-layout>
