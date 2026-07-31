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
                    </h1>
                    <p class="mt-1 text-xs text-neutral-400">
                        Presuňte zapísaných zamestnancov na pozície. Pozície sa dajú preusporiadať uchopením
                        <i class="fa-solid fa-grip-vertical text-[0.65rem]"></i>.
                    </p>
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

                    <a href="{{ route('calendar.show', ['date' => $weekStart->toDateString()]) }}"
                       title="Späť na zapisovanie"
                       class="ml-1 inline-flex h-11 items-center gap-2 rounded-xl border border-neutral-800 bg-neutral-900 px-4 text-sm font-semibold text-neutral-300 transition hover:border-neutral-700 hover:text-white">
                        <i class="fa-solid fa-arrow-left text-xs"></i>
                        Zapisovanie
                    </a>
                </div>
            </header>

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
</x-layout>
