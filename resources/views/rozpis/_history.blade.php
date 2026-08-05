{{-- Who changed this week's plan. A native <dialog> for the same reason the guide is one, and a
     dialog rather than a panel because history is looked at when something is wrong, not while
     working - it does not deserve permanent width next to seven drop columns.

     Rows are sittings, not writes: RozpisService::history() groups by person and minute, so
     filling a Friday reads as one entry with twenty lines under it rather than twenty entries. --}}
<dialog id="rozpis-history"
        class="w-[min(42rem,92vw)] rounded-2xl border border-neutral-800 bg-neutral-900 p-0 text-neutral-200 shadow-2xl backdrop:bg-neutral-950/80 backdrop:backdrop-blur-sm">
    <form method="dialog" class="flex items-start justify-between gap-4 border-b border-neutral-800 px-6 py-4">
        <div>
            <h2 class="flex items-center gap-2 text-lg font-bold text-white">
                <i class="fa-solid fa-clock-rotate-left text-sky-400"></i>
                História zmien
            </h2>
            <p class="mt-1 text-xs text-neutral-400">
                Kto čo v tomto týždni zmenil. Zoskupené po minútach, najnovšie hore.
            </p>
        </div>

        <button type="submit" aria-label="Zavrieť"
                class="shrink-0 rounded-lg p-2 text-neutral-500 transition hover:bg-neutral-800 hover:text-white">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </form>

    <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
        <ol class="flex flex-col gap-3">
            @foreach($history as $batch)
                <li class="rounded-lg border border-neutral-800 bg-neutral-950/50 px-4 py-3">
                    <div class="flex flex-wrap items-baseline gap-x-2 gap-y-0.5">
                        <span class="text-sm font-semibold text-neutral-100">{{ $batch['causer'] }}</span>

                        @if($batch['day'])
                            <span class="rounded bg-neutral-800/80 px-1.5 py-0.5 text-[0.65rem] font-semibold text-neutral-300">
                                {{ \Carbon\CarbonImmutable::parse($batch['day'])->locale('sk')->isoFormat('dd D.M.') }}
                            </span>
                        @endif

                        <span class="text-[0.7rem] text-neutral-500">{{ count($batch['changes']) }}x</span>

                        <span class="ml-auto shrink-0 tabular-nums text-[0.7rem] text-neutral-500">
                            {{ $batch['at']->format('d.m.Y H:i') }}
                        </span>
                    </div>

                    <ul class="mt-1.5 flex flex-col gap-1">
                        @foreach($batch['changes'] as $change)
                            <li class="flex gap-2 text-xs leading-relaxed text-neutral-400">
                                <i class="fa-solid fa-angle-right mt-1 text-[0.6rem] text-neutral-600"></i>
                                <span>{{ $change }}</span>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @endforeach
        </ol>
    </div>

    <form method="dialog" class="flex justify-end border-t border-neutral-800 px-6 py-4">
        <button type="submit"
                class="inline-flex min-h-10 items-center gap-2 rounded-md border border-neutral-700 bg-neutral-800 px-4 text-sm font-semibold text-neutral-200 transition hover:border-neutral-600 hover:text-white">
            Zavrieť
        </button>
    </form>
</dialog>
