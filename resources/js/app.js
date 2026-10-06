import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.css";
import "flatpickr/dist/themes/dark.css";
import { Slovak } from "flatpickr/dist/l10n/sk.js";
import { Chart, registerables } from "chart.js";

flatpickr.localize(Slovak);
window.flatpickr = flatpickr;

function toIsoDate(date) {
    return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
}

document.addEventListener('alpine:init', () => {
    Alpine.data('absenceRangePicker', (dateFrom, dateTo, openModal = false) => ({
        openModal,
        dateFrom,
        dateTo,
        sending: false,
        initFlatpickr() {
            if (typeof window.flatpickr !== 'function') return;
            window.flatpickr(this.$refs.rangeInput, {
                mode: 'range',
                dateFormat: 'Y-m-d',
                altInput: true,
                altInputClass: 'w-full rounded-lg border border-neutral-700 bg-neutral-800 px-3.5 py-2.5 text-base sm:text-sm text-neutral-100 placeholder-neutral-400 focus:border-neutral-500 focus:outline-none cursor-pointer',
                altFormat: 'j. n. Y',
                defaultDate: [this.dateFrom, this.dateTo],
                // The visible field is flatpickr's altInput; the <label for> and the hint point at it.
                onReady: (_dates, _str, fp) => {
                    if (!fp.altInput) return;
                    fp.altInput.id = 'absence-range';
                    fp.altInput.setAttribute('aria-describedby', 'absence-range-hint');
                },
                onChange: (selectedDates) => {
                    if (!selectedDates.length) return;
                    this.dateFrom = toIsoDate(selectedDates[0]);
                    this.dateTo = selectedDates.length === 2 ? toIsoDate(selectedDates[1]) : this.dateFrom;
                },
            });
        },
    }));

    Alpine.data('weekPicker', (lockedDates = [], weekStartDay = 3, currentWeekStart = '') => ({
        picker: null,
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
        init() {
            if (typeof window.flatpickr !== 'function') return;
            const self = this;
            this.currentWeekRange = this.getWeekRange(currentWeekStart);
            this.picker = window.flatpickr(this.$refs.pickerInput, {
                // On phones flatpickr would swap in the native date input: no week colouring,
                // and opening it from the trigger button is unreliable.
                disableMobile: true,
                dateFormat: 'Y-m-d',
                defaultDate: currentWeekStart,
                position: 'below center',
                positionElement: this.$refs.triggerButton,
                onDayCreate: (dObj, dStr, fp, dayElem) => {
                    const dateObj = dayElem.dateObj;
                    const year = dateObj.getFullYear();
                    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
                    const day = String(dateObj.getDate()).padStart(2, '0');
                    const dateFormatted = `${year}-${month}-${day}`;

                    const weekRange = self.getWeekRange(dateFormatted);
                    const isLocked = self.lockedDates.includes(weekRange[0]);
                    const isCurrentActive = self.currentWeekRange.includes(dateFormatted);

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
                    if (dateStr) window.location.href = '/week/' + dateStr;
                }
            });
        },
        open() {
            this.picker ? this.picker.open() : null;
        }
    }));
});

/**
 * A phone left open overnight comes back with an expired session; Livewire's own prompt for
 * that is an English "This page has expired" confirm.
 */
document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status !== 419) return;

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

function togglePasswordVisibility(inputId) {
    const passwordInput = document.getElementById(inputId);
    const icon = document.getElementById(inputId + 'Icon');

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
window.togglePasswordVisibility = togglePasswordVisibility;

Chart.register(...registerables);
window.Chart = Chart;

function renderProfileChart(newData) {
    const canvas = document.getElementById('barChart');
    if (!canvas) return;

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
    window.addEventListener('resize', () => {
        const nowMobile = isMobileNow();
        if (nowMobile === isMobile) return;
        isMobile = nowMobile;

        const chart = canvas._chartInstance;
        chart.data.labels = isMobile ? shortLabels : fullLabels;
        chart.data.datasets[0].maxBarThickness = isMobile ? 32 : 44;
        chart.options.scales.x.ticks.font.size = isMobile ? 11 : 12;
        chart.update();
    });
}
window.renderProfileChart = renderProfileChart;

document.addEventListener('DOMContentLoaded', function() {
    renderProfileChart();
});

// Welcome-page scroll choreography and auditorium. Dynamic imports so both are their own
// chunks and only the welcome page ever downloads them.
if (document.querySelector('[data-scroll-progress]')) {
    import('./welcome.js').then(({ mountWelcome }) => mountWelcome());
}

const seatFieldCanvas = document.querySelector('[data-seat-field]');
if (seatFieldCanvas) {
    import('./seat-field.js').then(({ mountSeatField }) => mountSeatField(seatFieldCanvas));
}
