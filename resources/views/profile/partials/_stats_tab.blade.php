<div x-show="tab === 'stats'" class="p-3.5 sm:p-6">
    <div x-show="currentPeriod.total === 0" style="display: none;">
        <x-empty-state
            icon="fa-chart-column"
            message="Žiadne odpracované zmeny v tomto období"
        >
            Používateľ nemá v zvolenom časovom rozsahu priradené žiadne zmeny v rozpise.
        </x-empty-state>
    </div>

    <div x-show="currentPeriod.total > 0">
        <div class="h-48 sm:h-64 w-full relative mb-4 sm:mb-6">
            <canvas id="barChart" data-chart='@json($arr)'></canvas>
        </div>

        {{-- Weekday Summary Grid - all 7 days in 1 row --}}
        <div class="pt-3.5 sm:pt-5 border-t border-neutral-800">
            <p class="text-[11px] sm:text-xs font-semibold uppercase tracking-wider text-neutral-400 mb-2.5">Rozpis zmien podľa dní v týždni</p>
            <div class="grid grid-cols-7 gap-1 sm:gap-2">
                @php
                    $shortDays = ['Pon', 'Uto', 'Str', 'Štv', 'Pia', 'Sob', 'Ned'];
                @endphp
                @foreach($shortDays as $idx => $dayLabel)
                    <div
                        class="p-1.5 sm:p-2.5 rounded-md sm:rounded-lg border border-neutral-800 bg-neutral-950/60 flex flex-col items-center justify-center text-center transition min-w-0"
                        :class="currentPeriod.counts[{{ $idx }}] > 0 && currentPeriod.counts[{{ $idx }}] === currentPeriod.max ? 'border-indigo-500/30 bg-indigo-500/10' : 'border-neutral-800 bg-neutral-950/60'"
                    >
                        <span
                            class="text-[10px] sm:text-xs font-medium text-neutral-400 truncate w-full"
                            :class="currentPeriod.counts[{{ $idx }}] > 0 && currentPeriod.counts[{{ $idx }}] === currentPeriod.max ? 'text-indigo-400 font-semibold' : 'text-neutral-400'"
                        >
                            {{ $dayLabel }}
                        </span>
                        <span
                            class="text-xs sm:text-base font-bold text-neutral-200 mt-0.5"
                            :class="currentPeriod.counts[{{ $idx }}] > 0 && currentPeriod.counts[{{ $idx }}] === currentPeriod.max ? 'text-white' : (currentPeriod.counts[{{ $idx }}] > 0 ? 'text-neutral-200' : 'text-neutral-400')"
                            x-text="currentPeriod.counts[{{ $idx }}]"
                        >
                            {{ $arr[$idx] ?? 0 }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
