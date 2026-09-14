<div class="bg-neutral-950 p-2.5 sm:px-6 sm:py-3 border-b border-neutral-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5 sm:gap-3">
    <nav class="grid grid-cols-2 sm:flex sm:items-center gap-1.5 sm:gap-2 w-full sm:w-auto" aria-label="Karty profilu">
        <button
            type="button"
            @click="tab = 'stats'; $nextTick(() => { if (typeof window.renderProfileChart === 'function') window.renderProfileChart(currentPeriod.counts); })"
            :class="tab === 'stats' ? 'bg-neutral-800 text-white font-semibold' : 'text-neutral-400 hover:text-neutral-200 hover:bg-neutral-900 font-medium'"
            class="inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 py-2 sm:py-1.5 rounded-md text-xs sm:text-sm transition focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer text-center"
        >
            <i class="fa-solid fa-chart-column text-xs" :class="tab === 'stats' ? 'text-indigo-400' : 'text-neutral-400'"></i>
            <span>Frekvencia zmien</span>
        </button>
        <button
            type="button"
            @click="tab = 'absences'"
            :class="tab === 'absences' ? 'bg-neutral-800 text-white font-semibold' : 'text-neutral-400 hover:text-neutral-200 hover:bg-neutral-900 font-medium'"
            class="inline-flex items-center justify-center gap-1.5 sm:gap-2 px-3 py-2 sm:py-1.5 rounded-md text-xs sm:text-sm transition focus:outline-none focus:ring-1 focus:ring-indigo-500 cursor-pointer text-center"
        >
            <i class="fa-solid fa-calendar-xmark text-xs" :class="tab === 'absences' ? 'text-indigo-400' : 'text-neutral-400'"></i>
            <span>Absencie</span>
            @if($activeAbsences->count() > 0)
                <span class="ml-1 inline-flex items-center justify-center px-1.5 py-0.2 text-[10px] font-bold rounded-full bg-indigo-500/20 text-indigo-300 border border-indigo-500/30">
                    {{ $activeAbsences->count() }}
                </span>
            @endif
        </button>
    </nav>

    {{-- Period Filter Pills (Only visible when tab === 'stats') --}}
    <div x-show="tab === 'stats'" class="grid grid-cols-3 sm:flex items-center gap-1 bg-neutral-900 p-1 rounded-lg border border-neutral-800 w-full sm:w-auto">
        <button
            type="button"
            @click="setPeriod('all')"
            :class="period === 'all' ? 'bg-neutral-800 text-white shadow-sm font-semibold' : 'text-neutral-400 hover:text-neutral-200 font-medium'"
            class="px-2 py-1.5 sm:px-2.5 sm:py-1 rounded-md text-[11px] sm:text-xs transition cursor-pointer text-center truncate"
        >
            Celé obdobie
        </button>
        <button
            type="button"
            @click="setPeriod('month')"
            :class="period === 'month' ? 'bg-neutral-800 text-white shadow-sm font-semibold' : 'text-neutral-400 hover:text-neutral-200 font-medium'"
            class="px-2 py-1.5 sm:px-2.5 sm:py-1 rounded-md text-[11px] sm:text-xs transition cursor-pointer text-center truncate"
        >
            Tento mesiac
        </button>
        <button
            type="button"
            @click="setPeriod('year')"
            :class="period === 'year' ? 'bg-neutral-800 text-white shadow-sm font-semibold' : 'text-neutral-400 hover:text-neutral-200 font-medium'"
            class="px-2 py-1.5 sm:px-2.5 sm:py-1 rounded-md text-[11px] sm:text-xs transition cursor-pointer text-center truncate"
        >
            Tento rok
        </button>
    </div>
</div>
