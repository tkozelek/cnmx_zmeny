<div class="grid grid-cols-3 gap-2 sm:gap-4">
    {{-- KPI 1: Total Days Worked --}}
    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-3 sm:p-5 text-center sm:text-left shadow-sm min-w-0">
        <p class="text-[10px] sm:text-xs font-medium text-neutral-400 uppercase tracking-wider truncate">Odpracované dni</p>
        <p class="text-xl sm:text-3xl font-bold text-white mt-0.5 sm:mt-1 tracking-tight truncate" x-text="currentPeriod.total">{{ $daysCount }}</p>
        <p class="hidden sm:block text-xs text-neutral-500 mt-1 truncate" x-text="currentPeriod.label">
            {{ match($activePeriod) { 'month' => 'Za posledný mesiac', 'year' => 'Za posledný rok', default => 'Za celé obdobie' } }}
        </p>
    </div>

    {{-- KPI 2: Most Active Weekday --}}
    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-3 sm:p-5 text-center sm:text-left shadow-sm min-w-0">
        <p class="text-[10px] sm:text-xs font-medium text-neutral-400 uppercase tracking-wider truncate">Najčastejší deň</p>
        <p class="text-xl sm:text-3xl font-bold text-white mt-0.5 sm:mt-1 tracking-tight truncate" x-text="currentPeriod.mostActiveDay">{{ $mostActiveDay ?? '—' }}</p>
        <p class="hidden sm:block text-xs text-neutral-500 mt-1 truncate">Najčastejšie v rozpise</p>
    </div>

    {{-- KPI 3: Active Absences --}}
    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-3 sm:p-5 text-center sm:text-left shadow-sm min-w-0">
        <p class="text-[10px] sm:text-xs font-medium text-neutral-400 uppercase tracking-wider truncate">Aktívne absencie</p>
        <p class="text-xl sm:text-3xl font-bold text-white mt-0.5 sm:mt-1 tracking-tight truncate">{{ $activeAbsences->count() }}</p>
        <p class="hidden sm:block text-xs text-neutral-500 mt-1 truncate">Platné alebo nadchádzajúce</p>
    </div>
</div>
