@props(['weekStart', 'weekEnd', 'previous', 'next' => null, 'locked' => false, 'lockedWeekStarts' => []])

@php
    $navBtnStyle = 'inline-flex h-11 sm:h-12 w-11 sm:w-12 items-center justify-center rounded-lg border border-neutral-700 bg-neutral-900 p-0 text-center font-bold text-neutral-100 transition hover:border-neutral-600 hover:bg-neutral-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-neutral-500 shadow-sm shrink-0 leading-none';

    $triggerStyle = $locked
        ? 'border-amber-500/80 bg-neutral-900 ring-1 ring-amber-500/30 hover:border-amber-400 text-white'
        : 'border-neutral-700 bg-neutral-900 hover:border-neutral-600 hover:text-white';
@endphp

<div class="flex items-center justify-between sm:justify-center gap-2 sm:gap-3 w-full sm:w-auto my-2">
    {{-- Previous Week --}}
    <a href="{{ route('calendar.show', ['date' => $previous->toDateString()]) }}"
       class="{{ $navBtnStyle }}"
       aria-label="Predchádzajúci týždeň"
       title="Predchádzajúci týždeň">
        <i class="fa-solid fa-chevron-left text-sm sm:text-base leading-none m-auto"></i>
    </a>

    {{-- Main Date Trigger --}}
    <div
        x-data="weekPicker(@js($lockedWeekStarts), {{ $currentTeam->weekStartDay() }}, '{{ $weekStart->toDateString() }}')"
        class="relative flex-1 sm:flex-initial flex items-center justify-center min-w-0"
    >
        <button
            x-ref="triggerButton"
            type="button"
            @click="open()"
            @class([
                'inline-flex h-11 sm:h-12 w-full sm:w-auto items-center justify-center rounded-lg border font-semibold text-neutral-100 transition focus:outline-none focus:ring-2 focus:ring-neutral-500 shadow-sm shrink min-w-0 leading-none text-xs sm:text-base gap-2 sm:gap-3 px-2.5 sm:px-6 py-0',
                $triggerStyle,
            ])
            title="Kliknutím otvoríte kalendár"
        >
            <i class="fa-regular fa-calendar text-xs sm:text-base text-neutral-400 shrink-0"></i>
            <span class="truncate tracking-wide font-bold">
                {{ $weekStart->format('d.m.') }} - {{ $weekEnd->format('d.m.Y') }}
            </span>

            @if($locked)
                <span class="inline-flex items-center gap-1 rounded bg-amber-600/90 border border-amber-500/40 px-1.5 py-0.5 text-[10px] sm:text-xs font-bold text-amber-100 shrink-0 shadow-sm">
                    <i class="fa-solid fa-lock text-[10px]"></i>
                    <span class="hidden sm:inline">Zamknutý</span>
                </span>
            @endif

            <i class="fa-solid fa-chevron-down text-[10px] sm:text-xs text-neutral-500 shrink-0"></i>
        </button>

        <input x-ref="pickerInput" type="text" class="sr-only" />
    </div>

    {{-- Next Week --}}
    @if($next)
        <a href="{{ route('calendar.show', ['date' => $next->toDateString()]) }}"
           class="{{ $navBtnStyle }}"
           aria-label="Nasledujúci týždeň"
           title="Nasledujúci týždeň">
            <i class="fa-solid fa-chevron-right text-sm sm:text-base leading-none m-auto"></i>
        </a>
    @else
        <span class="{{ $navBtnStyle }} cursor-not-allowed opacity-30" aria-hidden="true">
            <i class="fa-solid fa-chevron-right text-sm sm:text-base leading-none m-auto"></i>
        </span>
    @endif
</div>
