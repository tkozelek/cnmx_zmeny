// Only the calendar, absences and profile pages need these - loaded on first use instead of
// shipping ~200 kB of JS to every page.
let flatpickrLoading = null;

/** A failed import (flaky data, a stale chunk after deploy) is not cached, so the next use retries. */
function forgetFailedLoad(reset) {
    return (error) => {
        reset();
        throw error;
    };
}

const slovakDate = new Intl.DateTimeFormat('sk', { day: 'numeric', month: 'long', year: 'numeric' });

/** flatpickr's day cells are plain spans named in US order ("Október 22, 2026"). */
function nameDay(dayElem) {
    dayElem.setAttribute('role', 'button');
    dayElem.setAttribute('aria-label', slovakDate.format(dayElem.dateObj));
}

/** flatpickr's month arrows are plain spans with English labels - make them real buttons. */
function makeMonthArrowsButtons(fp) {
    [[fp.prevMonthNav, 'Predchádzajúci mesiac'], [fp.nextMonthNav, 'Nasledujúci mesiac']].forEach(([arrow, label]) => {
        if (!arrow) return;
        arrow.setAttribute('role', 'button');
        arrow.setAttribute('tabindex', '0');
        arrow.setAttribute('aria-label', label);
        arrow.addEventListener('keydown', (event) => {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            event.preventDefault();
            arrow.dispatchEvent(new MouseEvent('click', { bubbles: true }));
        });
    });
}

/** An inline calendar has no day in the tab order; give it exactly one, from which the arrow keys move. */
function keepOneDayTabbable(fp) {
    const day = fp.days.querySelector('.selected, .today:not(.flatpickr-disabled)')
        ?? fp.days.querySelector('.flatpickr-day:not(.flatpickr-disabled):not(.prevMonthDay):not(.nextMonthDay)');
    day?.setAttribute('tabindex', '0');
}

function loadFlatpickr() {
    flatpickrLoading ??= Promise.all([
        import('flatpickr'),
        import('flatpickr/dist/l10n/sk.js'),
        import('flatpickr/dist/flatpickr.css'),
        import('flatpickr/dist/themes/dark.css'),
    ]).then(([{ default: flatpickr }, { Slovak }]) => {
        flatpickr.localize(Slovak);
        window.flatpickr = flatpickr;
        return flatpickr;
    }).catch(forgetFailedLoad(() => { flatpickrLoading = null; }));

    return flatpickrLoading;
}

let chartLoading = null;

function loadChart() {
    chartLoading ??= import('chart.js').then(({ Chart, BarController, BarElement, CategoryScale, LinearScale, Tooltip }) => {
        Chart.register(BarController, BarElement, CategoryScale, LinearScale, Tooltip);
        return Chart;
    }).catch(forgetFailedLoad(() => { chartLoading = null; }));

    return chartLoading;
}

function toIsoDate(date) {
    return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
}

let sortableLoading = null;

function loadSortable() {
    sortableLoading ??= import('sortablejs')
        .then(({ default: Sortable }) => Sortable)
        .catch(forgetFailedLoad(() => { sortableLoading = null; }));

    return sortableLoading;
}

const timeFieldOptions = (initial) => ({
    enableTime: true,
    noCalendar: true,
    dateFormat: 'H:i',
    time_24hr: true,
    minuteIncrement: 15,
    defaultDate: initial,
});

function addQuickTimes(fp, times, onPick) {
    if (!times || !times.length) return;

    const row = document.createElement('div');
    row.className = 'flatpickr-quick-times';

    times.forEach((time) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.textContent = time;
        button.className = 'flatpickr-quick-time';
        button.addEventListener('click', () => onPick(time));
        row.appendChild(button);
    });

    fp.calendarContainer.prepend(row);
}

function pickQuickTime(fp) {
    return (time) => {
        fp.setDate(time, true);
        fp.close();
    };
}

document.addEventListener('alpine:init', () => {
    // `$wire.` is required: Livewire does not put component methods in Alpine's scope, and a bare
    // call throws an error Alpine swallows, leaving a drag that silently does nothing.
    Alpine.directive('sortable', (el, { expression }, { evaluate, cleanup }) => {
        let sortable = null;
        let destroyed = false;

        loadSortable().then((Sortable) => {
            if (destroyed) return;
            sortable = Sortable.create(el, {
                group: el.dataset.sortableGroup ?? 'sortable',
                animation: 150,
                ghostClass: 'opacity-40',
                onAdd: (event) => {
                    const id = event.item.dataset.sortableId;
                    if (!id) return;

                    evaluate(`$wire.${expression}(${id}, ${el.dataset.sortableTarget ?? 'null'})`);
                },
            });
        });

        cleanup(() => {
            destroyed = true;
            sortable?.destroy();
        });
    });

    // Only the grip drags: each row contains another sortable list of person cards.
    Alpine.directive('sortable-order', (el, { expression }, { evaluate, cleanup }) => {
        let sortable = null;
        let destroyed = false;

        loadSortable().then((Sortable) => {
            if (destroyed) return;
            sortable = Sortable.create(el, {
                animation: 150,
                handle: '[data-drag-handle]',
                draggable: '[data-slot-id]',
                ghostClass: 'opacity-40',
                onEnd: () => {
                    const ids = Array.from(el.children)
                        .map((node) => node.dataset.slotId)
                        .filter(Boolean);

                    if (ids.length) evaluate(`$wire.${expression}([${ids.join(',')}])`);
                },
            });
        });

        cleanup(() => {
            destroyed = true;
            sortable?.destroy();
        });
    });

    Alpine.data('timePicker', (property, initial = null, quickTimes = []) => ({
        picker: null,
        async init() {
            const flatpickr = await loadFlatpickr();
            // Livewire may have removed the component while the import was loading.
            if (!this.$refs.input?.isConnected) return;
            this.picker = flatpickr(this.$refs.input, {
                ...timeFieldOptions(initial),
                onChange: (dates, value) => this.$wire.set(property, value, false),
                onReady: (_dates, _str, fp) => addQuickTimes(fp, quickTimes, pickQuickTime(fp)),
            });
        },
        clear() {
            this.picker?.clear();
            this.$wire.set(property, null, false);
        },
        destroy() {
            this.picker?.destroy();
        },
    }));

    Alpine.data('slotTimePicker', (slotId, initial = null, quickTimes = []) => ({
        picker: null,
        async init() {
            const flatpickr = await loadFlatpickr();
            if (!this.$refs.input?.isConnected) return;
            this.picker = flatpickr(this.$refs.input, {
                ...timeFieldOptions(initial),
                onChange: (dates, value) => this.$wire.updateSlotTime(slotId, value || null),
                onReady: (_dates, _str, fp) => addQuickTimes(fp, quickTimes, pickQuickTime(fp)),
            });
        },
        destroy() {
            this.picker?.destroy();
        },
    }));

    Alpine.data('quickTimesEditor', (initial = []) => ({
        times: [...initial],
        newTime: '',
        picker: null,
        async init() {
            if (!this.$refs.newTimeInput) return;
            const flatpickr = await loadFlatpickr();
            if (!this.$refs.newTimeInput?.isConnected) return;
            this.picker = flatpickr(this.$refs.newTimeInput, {
                ...timeFieldOptions(null),
                disableMobile: true,
                onChange: (dates, value) => { this.newTime = value; },
            });
        },
        add() {
            if (!this.newTime || this.times.includes(this.newTime)) return;
            this.times.push(this.newTime);
            this.times.sort();
            this.newTime = '';
            this.picker?.clear();
        },
        remove(index) {
            this.times.splice(index, 1);
        },
        destroy() {
            this.picker?.destroy();
        },
    }));

    Alpine.data('weekJump', (current, urlTemplate) => ({
        picker: null,
        async init() {
            const flatpickr = await loadFlatpickr();
            if (!this.$refs.input?.isConnected) return;
            this.picker = flatpickr(this.$refs.input, {
                disableMobile: true,
                dateFormat: 'Y-m-d',
                defaultDate: current,
                positionElement: this.$refs.trigger,
                position: 'below center',
                onChange: (dates, value) => {
                    if (value) window.location.href = urlTemplate.replace('__DATE__', value);
                },
            });
        },
        open() {
            this.picker?.open();
        },
        destroy() {
            this.picker?.destroy();
        },
    }));

    Alpine.data('absenceRangePicker', (dateFrom, dateTo, openModal = false, minDate = null) => ({
        openModal,
        dateFrom,
        dateTo,
        sending: false,
        picker: null,
        async initFlatpickr() {
            const flatpickr = await loadFlatpickr();
            if (!this.$refs.rangeInput?.isConnected) return;
            this.picker?.destroy();
            this.picker = flatpickr(this.$refs.rangeInput, {
                mode: 'range',
                // Inline inside the dialog, not a popup at the end of <body>: the dialog's focus trap
                // would keep keyboard users out of a popup, and its overflow would clip one.
                inline: true,
                // Days before the reporting deadline would only fail validation.
                minDate,
                dateFormat: 'Y-m-d',
                altInput: true,
                altInputClass: 'w-full rounded-lg border border-neutral-500 bg-neutral-800 px-3.5 py-2.5 text-base sm:text-sm text-neutral-100 placeholder-neutral-400 focus:border-neutral-500 focus:outline-none cursor-pointer',
                altFormat: 'j.n.Y',
                defaultDate: [this.dateFrom, this.dateTo],
                // The visible field is flatpickr's altInput; the <label for> and the hint point at it.
                onReady: (_dates, _str, fp) => {
                    makeMonthArrowsButtons(fp);
                    keepOneDayTabbable(fp);
                    if (!fp.altInput) return;
                    fp.altInput.id = 'absence-range';
                    // The server's error summary, when there is one, describes the dates too.
                    fp.altInput.setAttribute('aria-describedby', document.getElementById('absence-errors') ? 'absence-range-hint absence-errors' : 'absence-range-hint');
                },
                onDayCreate: (_dates, _str, _fp, dayElem) => nameDay(dayElem),
                onMonthChange: (_dates, _str, fp) => keepOneDayTabbable(fp),
                onChange: (selectedDates, _str, fp) => {
                    keepOneDayTabbable(fp);
                    if (!selectedDates.length) return;
                    this.dateFrom = toIsoDate(selectedDates[0]);
                    this.dateTo = selectedDates.length === 2 ? toIsoDate(selectedDates[1]) : this.dateFrom;
                },
            });
        },
        destroy() {
            this.picker?.destroy();
        },
    }));

    Alpine.data('weekPicker', (lockedDates = [], weekStartDay = 3, currentWeekStart = '', options = {}) => ({
        picker: null,
        isOpen: false,
        lockedDates: lockedDates || [],
        weekStartDay: weekStartDay ?? 3,
        currentWeekRange: [],
        getWeekRange(dateStr) {
            if (!dateStr) return [];
            const d = new Date(dateStr + 'T00:00:00');
            const jsStartDay = (this.weekStartDay + 1) % 7;
            let diff = d.getDay() - jsStartDay;
            if (diff < 0) diff += 7;
            const start = new Date(d);
            start.setDate(d.getDate() - diff);
            const range = [];
            for (let i = 0; i < 7; i++) {
                const curr = new Date(start);
                curr.setDate(start.getDate() + i);
                const year = curr.getFullYear();
                const month = String(curr.getMonth() + 1).padStart(2, '0');
                const day = String(curr.getDate()).padStart(2, '0');
                range.push(`${year}-${month}-${day}`);
            }
            return range;
        },
        async init() {
            const flatpickr = await loadFlatpickr();
            if (!this.$refs.pickerInput?.isConnected) return;
            const self = this;
            this.currentWeekRange = this.getWeekRange(currentWeekStart);
            this.picker = flatpickr(this.$refs.pickerInput, {
                // On phones flatpickr would swap in the native date input: no week colouring,
                // and opening it from the trigger button is unreliable.
                disableMobile: true,
                dateFormat: 'Y-m-d',
                defaultDate: currentWeekStart,
                // Rows start on the cinema's first weekday, so every work week is exactly one row.
                locale: { firstDayOfWeek: (this.weekStartDay + 1) % 7 },
                // Weeks past the team's lookahead aren't open yet - same limit as the "next" arrow.
                maxDate: options.maxDate,
                position: 'below center',
                positionElement: this.$refs.triggerButton,
                onReady: (_dates, _str, fp) => {
                    makeMonthArrowsButtons(fp);
                    fp.monthsDropdownContainer?.setAttribute('aria-label', 'Mesiac');
                    fp.currentYearElement?.setAttribute('aria-label', 'Rok');
                    fp.calendarContainer.setAttribute('role', 'dialog');
                    fp.calendarContainer.setAttribute('aria-label', 'Výber týždňa');

                    const footer = document.createElement('div');
                    footer.className = 'week-picker-footer';
                    footer.innerHTML = `
                        <ul class="week-picker-legend" aria-hidden="true">
                            <li><span class="week-picker-swatch is-open"></span>Otvorený</li>
                            <li><span class="week-picker-swatch is-locked"></span>Zamknutý</li>
                            <li><span class="week-picker-swatch is-current"></span>Zobrazený</li>
                        </ul>`;
                    const today = document.createElement('a');
                    today.className = 'week-picker-today';
                    today.href = options.currentWeekUrl ?? '/tyzdne';
                    today.textContent = 'Tento týždeň';
                    footer.appendChild(today);
                    fp.calendarContainer.appendChild(footer);
                },
                // Focus goes into the calendar so the arrow keys work, and back to the button after.
                onOpen: (_dates, _str, fp) => {
                    self.isOpen = true;
                    requestAnimationFrame(() => (fp.selectedDateElem || fp.todayDateElem || fp.days.querySelector('.flatpickr-day'))?.focus());
                },
                onClose: (_dates, _str, fp) => {
                    self.isOpen = false;
                    // A frame later: flatpickr moves focus to its own (hidden) input after this hook.
                    requestAnimationFrame(() => {
                        const focused = document.activeElement;
                        if (focused === self.$refs.pickerInput || focused === document.body || fp.calendarContainer.contains(focused)) {
                            self.$refs.triggerButton.focus();
                        }
                    });
                },
                onDayCreate: (dObj, dStr, fp, dayElem) => {
                    const dateObj = dayElem.dateObj;
                    const year = dateObj.getFullYear();
                    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                    const day = String(dateObj.getDate()).padStart(2, '0');
                    const dateFormatted = `${year}-${month}-${day}`;

                    const weekRange = self.getWeekRange(dateFormatted);
                    const isLocked = self.lockedDates.includes(weekRange[0]);
                    const isCurrentActive = self.currentWeekRange.includes(dateFormatted);

                    // The colours say locked/open/current - say it to screen readers too.
                    nameDay(dayElem);
                    dayElem.setAttribute('aria-label', [
                        dayElem.getAttribute('aria-label'),
                        isLocked ? 'zamknutý týždeň' : 'otvorený týždeň',
                        isCurrentActive ? 'zobrazený' : null,
                    ].filter(Boolean).join(', '));

                    if (isLocked) {
                        dayElem.classList.add('is-locked-week-day');
                        dayElem.title = 'Zamknutý týždeň (' + weekRange[0] + ')';
                        if (dateFormatted === weekRange[0]) dayElem.classList.add('is-locked-week-start');
                        if (dateFormatted === weekRange[6]) dayElem.classList.add('is-locked-week-end');
                    } else {
                        dayElem.classList.add('is-open-week-day');
                        dayElem.title = 'Otvorený týždeň (' + weekRange[0] + ')';
                        if (dateFormatted === weekRange[0]) dayElem.classList.add('is-open-week-start');
                        if (dateFormatted === weekRange[6]) dayElem.classList.add('is-open-week-end');
                    }

                    if (isCurrentActive) {
                        dayElem.classList.add('is-active-week-day');
                        if (dateFormatted === self.currentWeekRange[0]) dayElem.classList.add('is-week-start');
                        if (dateFormatted === self.currentWeekRange[6]) dayElem.classList.add('is-week-end');
                    }

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
                                if (isLocked) {
                                    el.classList.add('week-hover-locked');
                                } else {
                                    el.classList.add('week-hover-open');
                                }
                            }
                        });
                    });

                    dayElem.addEventListener('mouseleave', () => {
                        fp.calendarContainer.querySelectorAll('.flatpickr-day.week-hover').forEach(el => {
                            el.classList.remove('week-hover', 'week-hover-locked', 'week-hover-open');
                        });
                    });
                },
                onChange: (selectedDates, dateStr) => {
                    if (dateStr) window.location.href = (options.weekUrl ?? '/week/__DATE__').replace('__DATE__', dateStr);
                }
            });
        },
        open() {
            this.picker ? this.picker.open() : null;
        },
        destroy() {
            this.picker?.destroy();
        }
    }));
});

/**
 * A phone left open overnight comes back with an expired session; Livewire's own prompt for
 * that is an English "This page has expired" confirm.
 */
document.addEventListener('livewire:init', () => {
    Livewire.interceptRequest(({ onError }) => {
        onError(({ response, preventDefault }) => {
            if (response.status !== 419) return;

            preventDefault();
            if (confirm('Stránka bola dlho nečinná. Načítať ju znova?')) window.location.reload();
        });
    });
});

function showToast(message, type = 'success', icon = '') {
    window.dispatchEvent(new CustomEvent('toast', {
        detail: { message, type, icon }
    }));
}

/**
 * The calendar's two switches drive a class on <html> (see app.css). Each is remembered per
 * browser, so a manager doesn't switch losovanie back on for every week, and re-synced on
 * pageshow because a back navigation restores the checkbox but not the class.
 */
const calendarToggles = [
    ['names_checkbox', 'hide-names', false],
    ['draw_checkbox', 'show-draw', true],
];

function syncCalendarToggles(restoreSaved) {
    for (const [id, cssClass, classWhenChecked] of calendarToggles) {
        const input = document.getElementById(id);
        if (!input) continue;

        if (restoreSaved) {
            try {
                const saved = localStorage.getItem(id);
                if (saved !== null) input.checked = saved === '1';
            } catch (e) { /* storage blocked - keep the default */ }
        }

        document.documentElement.classList.toggle(cssClass, input.checked === classWhenChecked);
    }
}

document.addEventListener('DOMContentLoaded', () => syncCalendarToggles(true));
window.addEventListener('pageshow', () => syncCalendarToggles(false));

document.addEventListener('change', function (event) {
    if (!calendarToggles.some(([id]) => id === event.target.id)) return;

    try {
        localStorage.setItem(event.target.id, event.target.checked ? '1' : '0');
    } catch (e) { /* storage blocked - the switch still works for this page */ }

    syncCalendarToggles(false);
});

function togglePasswordVisibility(inputId, button) {
    const passwordInput = document.getElementById(inputId);
    const icon = document.getElementById(inputId + 'Icon');
    const show = passwordInput.type === 'password';

    passwordInput.type = show ? 'text' : 'password';
    icon.classList.toggle('fa-eye', !show);
    icon.classList.toggle('fa-eye-slash', show);
    button?.setAttribute('aria-pressed', String(show));
}
window.togglePasswordVisibility = togglePasswordVisibility;

let profileChart = null;
let profileChartResize = null;

async function renderProfileChart(newData) {
    const canvas = document.getElementById('barChart');
    if (!canvas) return;

    const Chart = await loadChart();

    let data = newData || window.chartData;
    if (!data && canvas.dataset.chart) {
        try {
            data = JSON.parse(canvas.dataset.chart);
        } catch (e) {
            data = [0, 0, 0, 0, 0, 0, 0];
        }
    }
    if (!data) return;

    if (canvas._chartInstance) {
        canvas._chartInstance.data.datasets[0].data = data;
        canvas._chartInstance.update();
        return;
    }

    // A Livewire morph may have replaced the canvas: drop the old chart and its resize handler.
    profileChart?.destroy();
    window.removeEventListener('resize', profileChartResize);

    const shortLabels = ['Pon', 'Uto', 'Str', 'Štv', 'Pia', 'Sob', 'Ned'];
    const fullLabels = ['Pondelok', 'Utorok', 'Streda', 'Štvrtok', 'Piatok', 'Sobota', 'Nedeľa'];
    const isMobileNow = () => window.innerWidth < 640;
    let isMobile = isMobileNow();

    const ctx = canvas.getContext('2d');
    canvas._chartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: isMobile ? shortLabels : fullLabels,
            datasets: [{
                label: 'Zapísané dni',
                data: data,
                // brand-500 / brand-400 (see the --color-brand-* tokens in app.css)
                backgroundColor: 'rgba(14, 165, 233, 0.85)',
                hoverBackgroundColor: 'rgba(56, 189, 248, 1)',
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: isMobile ? 32 : 44,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: '#171717',
                    titleColor: '#f5f5f5',
                    bodyColor: '#d4d4d4',
                    borderColor: '#262626',
                    borderWidth: 1,
                    padding: 10,
                    cornerRadius: 6,
                    displayColors: false,
                    callbacks: {
                        title: function(tooltipItems) {
                            return fullLabels[tooltipItems[0].dataIndex] || '';
                        },
                        label: function(context) {
                            const val = context.parsed.y;
                            if (val === 1) return '1 odpracovaný deň';
                            if (val >= 2 && val <= 4) return `${val} odpracované dni`;
                            return `${val} odpracovaných dní`;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#a3a3a3',
                        maxRotation: 0,
                        minRotation: 0,
                        autoSkip: false,
                        font: {
                            family: 'inherit',
                            size: isMobile ? 11 : 12,
                            weight: 500
                        }
                    }
                },
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(255, 255, 255, 0.06)'
                    },
                    ticks: {
                        precision: 0,
                        color: '#a3a3a3',
                        font: {
                            family: 'inherit',
                            size: 11
                        }
                    },
                    grace: '8%'
                }
            }
        }
    });

    // Chart.js's own `responsive: true` already rescales the canvas itself on resize; this
    // only swaps the mobile/desktop label set, tick font size and bar thickness once the
    // viewport actually crosses the 640px breakpoint, since those aren't part of Chart.js's
    // own resize handling and were otherwise frozen at whatever size the page first loaded at.
    profileChart = canvas._chartInstance;
    profileChartResize = () => {
        const nowMobile = isMobileNow();
        if (nowMobile === isMobile) return;
        isMobile = nowMobile;

        const chart = canvas._chartInstance;
        chart.data.labels = isMobile ? shortLabels : fullLabels;
        chart.data.datasets[0].maxBarThickness = isMobile ? 32 : 44;
        chart.options.scales.x.ticks.font.size = isMobile ? 11 : 12;
        chart.update();
    };
    window.addEventListener('resize', profileChartResize);
}
window.renderProfileChart = renderProfileChart;

document.addEventListener('DOMContentLoaded', function() {
    renderProfileChart();
});

// After a deploy an open tab still references the old chunk hashes - reload once onto the new build.
// The guard is cleared once a page has stayed up for a while, so the next deploy reloads too.
window.addEventListener('load', () => setTimeout(() => {
    try {
        sessionStorage.removeItem('reloaded-for-new-build');
    } catch (e) {
        // storage blocked - nothing to clear
    }
}, 10000));

window.addEventListener('vite:preloadError', () => {
    try {
        if (sessionStorage.getItem('reloaded-for-new-build')) return;
        sessionStorage.setItem('reloaded-for-new-build', '1');
    } catch (e) {
        return; // storage blocked - no way to stop a reload loop, so don't start one
    }
    window.location.reload();
});

// Welcome-page scroll choreography and auditorium. Dynamic imports so both are their own
// chunks and only the welcome page ever downloads them. The page hides its content until the
// choreography reveals it (html.js), so if that fails the hiding is dropped and the page shows as is.
const showWelcomeWithoutMotion = () => document.documentElement.classList.remove('js');

if (document.querySelector('[data-scroll-progress]')) {
    import('./welcome.js')
        .then(({ mountWelcome }) => mountWelcome())
        .catch(showWelcomeWithoutMotion);
}

const seatFieldCanvas = document.querySelector('[data-seat-field]');
if (seatFieldCanvas) {
    import('./seat-field.js')
        .then(({ mountSeatField }) => mountSeatField(seatFieldCanvas))
        .catch(() => {});
}
