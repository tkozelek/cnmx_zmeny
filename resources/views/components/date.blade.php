@props(['weekStart', 'weekEnd', 'previous', 'next' => null, 'locked' => false, 'lockedWeekStarts' => []])

@php
    $navBtnStyle = 'inline-flex h-12 sm:h-14 w-12 sm:w-14 items-center justify-center rounded-lg border border-neutral-700 bg-neutral-900 p-0 text-center font-bold text-neutral-100 transition hover:border-neutral-600 hover:bg-neutral-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-neutral-500 shadow-sm shrink-0 leading-none';

    $triggerStyle = $locked
        ? 'border-amber-500/80 bg-neutral-900 ring-1 ring-amber-500/30 hover:border-amber-400 text-white'
        : 'border-neutral-700 bg-neutral-900 hover:border-neutral-600 hover:text-white';
@endphp

<div class="flex flex-wrap sm:flex-nowrap items-center justify-center gap-3 w-full sm:w-auto my-2">
    {{-- Previous Week --}}
    <a href="{{ route('calendar.show', ['date' => $previous->toDateString()]) }}"
       class="{{ $navBtnStyle }} order-2 sm:order-none"
       aria-label="Predchádzajúci týždeň"
       title="Predchádzajúci týždeň">
        <i class="fa-solid fa-chevron-left text-base sm:text-lg leading-none m-auto"></i>
    </a>

    {{-- Main Date Trigger --}}
    <div
        x-data="weekPicker(@js($lockedWeekStarts), {{ $currentTeam->weekStartDay() }}, '{{ $weekStart->toDateString() }}')"
        class="relative flex items-center justify-center min-w-0 order-1 sm:order-none basis-full sm:basis-auto"
    >
        <button
            x-ref="triggerButton"
            type="button"
            @click="open()"
            @class([
                'inline-flex h-12 sm:h-14 w-full sm:w-auto items-center justify-center rounded-lg border font-bold text-neutral-100 transition focus:outline-none focus:ring-2 focus:ring-neutral-500 shadow-sm shrink sm:shrink-0 min-w-0 leading-none text-base sm:text-lg gap-3 sm:gap-4 px-4 sm:px-8 py-0',
                $triggerStyle,
            ])
            title="Kliknutím otvoríte kalendár"
        >
            <i class="fa-regular fa-calendar text-base sm:text-xl text-neutral-400 shrink-0 leading-none"></i>
            <span class="min-w-0 truncate tracking-wide leading-none">{{ $weekStart->format('d.m.') }} - {{ $weekEnd->format('d.m.Y') }}</span>

            @if($locked)
                <span class="inline-flex items-center gap-1.5 rounded-md bg-amber-600 border-b border-amber-700 px-2.5 py-1 text-xs font-bold text-white shrink-0 shadow-sm">
                    <i class="fa-solid fa-lock text-xs text-amber-200"></i> Zamknutý
                </span>
            @endif

            <i class="fa-solid fa-chevron-down text-xs sm:text-sm text-neutral-500 shrink-0 leading-none"></i>
        </button>

        <input x-ref="pickerInput" type="text" class="sr-only" />
    </div>

    {{-- Next Week --}}
    @if($next)
        <a href="{{ route('calendar.show', ['date' => $next->toDateString()]) }}"
           class="{{ $navBtnStyle }} order-3 sm:order-none"
           aria-label="Nasledujúci týždeň"
           title="Nasledujúci týždeň">
            <i class="fa-solid fa-chevron-right text-base sm:text-lg leading-none m-auto"></i>
        </a>
    @else
        <span class="{{ $navBtnStyle }} order-3 sm:order-none cursor-not-allowed opacity-30" aria-hidden="true">
            <i class="fa-solid fa-chevron-right text-base sm:text-lg leading-none m-auto"></i>
        </span>
    @endif
</div>
