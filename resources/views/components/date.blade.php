@props(['weekStart', 'weekEnd', 'previous', 'next' => null, 'locked' => false, 'lockedWeekStarts' => []])

@php
    $navBtnStyle = 'inline-flex h-14 sm:h-16 w-14 sm:w-16 items-center justify-center rounded-xl border-2 border-neutral-800 bg-neutral-900 p-0 text-center font-bold text-neutral-100 transition hover:border-neutral-700 hover:bg-neutral-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-neutral-500 shadow-md shrink-0 leading-none';

    $triggerStyle = $locked
        ? 'border-sky-500/60 bg-neutral-900 ring-1 ring-sky-500/30 hover:border-sky-400 shadow-sky-950/40'
        : 'border-neutral-800 bg-neutral-900 hover:border-neutral-700 hover:text-white';
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
        x-data="{
            picker: null,
            lockedDates: @js($lockedWeekStarts),
            weekStartDay: {{ $currentTeam->weekStartDay() }}, // 3 = Thursday by default
            getWeekRange(dateStr) {
                if (!dateStr) return [];
                const d = new Date(dateStr + 'T00:00:00');
                // Calculate day offset based on team week_start_day
                // Flatpickr JS day: 0 = Sun, 1 = Mon ... 4 = Thu
                // Convert weekStartDay (0=Mon .. 6=Sun) to JS day: (weekStartDay + 1) % 7
                const jsStartDay = (this.weekStartDay + 1) % 7;
                let diff = d.getDay() - jsStartDay;
                if (diff < 0) diff += 7;
                
                const start = new Date(d);
                start.setDate(d.getDate() - diff);
                
                const range = [];
                for (let i = 0; i < 7; i++) {
                    const curr = new Date(start);
                    curr.setDate(start.getDate() + i);
                    // Format Y-m-d
                    const year = curr.getFullYear();
                    const month = String(curr.getMonth() + 1).padStart(2, '0');
                    const day = String(curr.getDate()).padStart(2, '0');
                    range.push(`${year}-${month}-${day}`);
                }
                return range;
            },
            currentWeekRange: [],
            init() {
                if (typeof window.flatpickr !== 'function') return;
                const self = this;
                this.currentWeekRange = this.getWeekRange('{{ $weekStart->toDateString() }}');
                this.picker = window.flatpickr($refs.pickerInput, {
                    dateFormat: 'Y-m-d',
                    defaultDate: '{{ $weekStart->toDateString() }}',
                    position: 'below center',
                    positionElement: $refs.triggerButton,
                    onDayCreate: (dObj, dStr, fp, dayElem) => {
                        const dateObj = dayElem.dateObj;
                        const year = dateObj.getFullYear();
                        const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                        const day = String(dateObj.getDate()).padStart(2, '0');
                        const dateFormatted = `${year}-${month}-${day}`;
                        
                        const weekRange = self.getWeekRange(dateFormatted);
                        const isLocked = self.lockedDates.includes(weekRange[0]);
                        
                        if (isLocked) {
                            dayElem.classList.add('is-locked-week-day');
                            dayElem.title = 'Zamknutý týždeň (' + weekRange[0] + ')';
                        }

                        if (self.currentWeekRange.includes(dateFormatted)) {
                            dayElem.classList.add('is-active-week-day');
                            if (dateFormatted === self.currentWeekRange[0]) dayElem.classList.add('is-week-start');
                            if (dateFormatted === self.currentWeekRange[6]) dayElem.classList.add('is-week-end');
                        }

                        // Add hover effect for full week block
                        dayElem.addEventListener('mouseenter', () => {
                            fp.calendarContainer.querySelectorAll('.flatpickr-day').forEach(el => {
                                const elDateObj = el.dateObj;
                                if (!elDateObj) return;
                                const ey = elDateObj.getFullYear();
                                const em = String(elDateObj.getMonth() + 1).padStart(2, '0');
                                const ed = String(elDateObj.getDate()).padStart(2, '0');
                                const ef = `${ey}-${em}-${ed}`;
                                if (weekRange.includes(ef)) {
                                    el.classList.add('week-hover');
                                }
                            });
                        });
                        dayElem.addEventListener('mouseleave', () => {
                            fp.calendarContainer.querySelectorAll('.flatpickr-day.week-hover').forEach(el => {
                                el.classList.remove('week-hover');
                            });
                        });
                    },
                    onChange: (selectedDates, dateStr) => {
                        if (dateStr) window.location.href = '/week/' + dateStr;
                    }
                });
            }
        }"
        class="relative flex items-center justify-center min-w-0 order-1 sm:order-none basis-full sm:basis-auto"
    >
        <button
            x-ref="triggerButton"
            type="button"
            @click="picker ? picker.open() : null"
            @class([
                'inline-flex h-14 sm:h-16 w-full sm:w-auto items-center justify-center rounded-xl border-2 font-bold text-neutral-100 transition focus:outline-none focus:ring-2 focus:ring-neutral-500 shadow-md shrink sm:shrink-0 min-w-0 leading-none text-base sm:text-xl gap-3 sm:gap-4 px-4 sm:px-8 py-0',
                $triggerStyle,
            ])
            title="Kliknutím otvoríte kalendár"
        >
            <i class="fa-regular fa-calendar text-base sm:text-xl text-neutral-400 shrink-0 leading-none"></i>
            <span class="min-w-0 truncate tracking-wide leading-none">{{ $weekStart->format('d.m.') }} - {{ $weekEnd->format('d.m.Y') }}</span>

            @if($locked)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-sky-500/10 border border-sky-500/30 px-2.5 py-1 text-xs font-semibold text-sky-400 shrink-0">
                    <i class="fa-solid fa-lock text-[0.65rem]"></i> Zamknutý
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
