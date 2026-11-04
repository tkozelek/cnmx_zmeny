@use('App\Support\SlovakDate')
@props(['weekStart', 'weekEnd', 'previous', 'next' => null, 'locked' => false, 'lockedWeekStarts' => [], 'pickerOptions' => []])

<div class="flex items-center justify-between sm:justify-center gap-2 sm:gap-3 w-full sm:w-auto my-2">
    {{-- Previous Week --}}
    <a href="{{ route('calendar.show', ['date' => $previous->toDateString()]) }}" data-print-hide
       class="inline-flex h-11 sm:h-12 w-11 sm:w-12 items-center justify-center rounded-lg border border-neutral-700 bg-neutral-900 p-0 text-center font-bold text-neutral-100 transition hover:border-neutral-600 hover:bg-neutral-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-neutral-500 shadow-sm shrink-0 leading-none"
       aria-label="Predchádzajúci týždeň"
       title="Predchádzajúci týždeň">
        <i class="fa-solid fa-chevron-left text-sm sm:text-base leading-none m-auto"></i>
    </a>

    {{-- Main Date Trigger --}}
    <div
        x-data="weekPicker(@js($lockedWeekStarts), {{ $currentTeam->weekStartDay() }}, '{{ $weekStart->toDateString() }}', @js($pickerOptions))"
        class="relative flex-1 sm:flex-initial flex items-center justify-center min-w-0"
    >
        <button
            x-ref="triggerButton"
            type="button"
            @click="open()"
            aria-haspopup="dialog"
            :aria-expanded="isOpen.toString()"
            @class([
                'inline-flex h-11 sm:h-12 w-full sm:w-auto items-center justify-center rounded-lg border font-semibold text-neutral-100 transition focus:outline-none focus:ring-2 focus:ring-neutral-500 shadow-sm shrink min-w-0 leading-none text-xs sm:text-base gap-2 sm:gap-3 px-2.5 sm:px-6 py-0',
                'border-neutral-500 bg-neutral-900 hover:border-neutral-400 text-white' => $locked,
                'border-neutral-700 bg-neutral-900 hover:border-neutral-600 hover:text-white' => ! $locked,
            ])
            title="Vyber týždeň"
        >
            <i class="fa-regular fa-calendar text-xs sm:text-base text-neutral-400 shrink-0" aria-hidden="true"></i>
            <span class="sr-only">Vybrať týždeň, zobrazený:</span>
            <span class="truncate tracking-wide font-bold">
                {{ SlovakDate::range($weekStart, $weekEnd) }}
            </span>

            @if($locked)
                <span class="inline-flex items-center gap-1 rounded border border-neutral-600 bg-neutral-800 px-1.5 py-0.5 text-xs font-semibold text-neutral-200 shrink-0">
                    <i class="fa-solid fa-lock text-[10px]" aria-hidden="true"></i>
                    <span class="sr-only sm:not-sr-only">Zamknutý</span>
                </span>
            @endif

            <i class="fa-solid fa-chevron-down text-[10px] sm:text-xs text-neutral-400 shrink-0" aria-hidden="true"></i>
        </button>

        {{-- flatpickr's anchor only - the button above is the control. --}}
        <input x-ref="pickerInput" type="text" class="sr-only" tabindex="-1" aria-hidden="true" />
    </div>

    {{-- Next Week --}}
    @if($next)
        <a href="{{ route('calendar.show', ['date' => $next->toDateString()]) }}" data-print-hide
           class="inline-flex h-11 sm:h-12 w-11 sm:w-12 items-center justify-center rounded-lg border border-neutral-700 bg-neutral-900 p-0 text-center font-bold text-neutral-100 transition hover:border-neutral-600 hover:bg-neutral-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-neutral-500 shadow-sm shrink-0 leading-none"
           aria-label="Nasledujúci týždeň"
           title="Nasledujúci týždeň">
            <i class="fa-solid fa-chevron-right text-sm sm:text-base leading-none m-auto"></i>
        </a>
    @else
        <span data-print-hide class="inline-flex h-11 sm:h-12 w-11 sm:w-12 items-center justify-center rounded-lg border border-neutral-700 bg-neutral-900 p-0 text-center font-bold text-neutral-100 transition hover:border-neutral-600 hover:bg-neutral-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-neutral-500 shadow-sm shrink-0 leading-none cursor-not-allowed opacity-30" role="link" aria-disabled="true" aria-label="Ďalšie týždne ešte nie sú otvorené" title="Ďalšie týždne ešte nie sú otvorené.">
            <i class="fa-solid fa-chevron-right text-sm sm:text-base leading-none m-auto" aria-hidden="true"></i>
        </span>
    @endif
</div>
