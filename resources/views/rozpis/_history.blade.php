{{-- Who changed this week's plan. A dialog rather than a panel because history is looked at when
     something is wrong, not while working - it does not deserve permanent width next to seven drop
     columns. The chrome is shared with the guide panel; see x-rozpis.dialog.

     Rows are sittings, not writes: RozpisService::history() groups by person and minute, so
     filling a Friday reads as one entry with twenty lines under it rather than twenty entries. --}}
<x-rozpis.dialog id="rozpis-history"
                 icon="fa-clock-rotate-left"
                 title="História zmien"
                 subtitle="Kto čo v tomto týždni zmenil. Zoskupené po minútach, najnovšie hore."
                 width="42rem">
    <x-slot:footer>
        <button type="submit"
                class="inline-flex min-h-10 items-center gap-2 rounded-md border border-neutral-700 bg-neutral-800 px-4 text-sm font-semibold text-neutral-200 transition hover:border-neutral-600 hover:text-white">
            Zavrieť
        </button>
    </x-slot:footer>

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
</x-rozpis.dialog>
