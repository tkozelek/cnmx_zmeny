import flatpickr from "flatpickr";
import "flatpickr/dist/flatpickr.css";
import "flatpickr/dist/themes/dark.css";
import { Slovak } from "flatpickr/dist/l10n/sk.js";
import { Chart, registerables } from "chart.js";
import Sortable from "sortablejs";

flatpickr.localize(Slovak);
window.flatpickr = flatpickr;

function toIsoDate(date) {
    return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
}

document.addEventListener('alpine:init', () => {
    /**
     * Drag a card between lists and tell Livewire where it landed.
     *
     * `x-sortable="place"` names the Livewire method; the element's data attributes carry the
     * rest. `sortable-group` decides which lists exchange cards (scoped per day, so a person can
     * never be dragged into another day), and `sortable-target` — the id of the slot this list
     * belongs to — is passed as the second argument alongside the dropped card's `sortable-id`.
     * A list with no target sends null: that is the unassigned pool, and null is exactly what
     * "not placed anywhere" means server-side.
     *
     * Only onAdd fires a request: reordering inside one list changes nothing that is stored.
     */
    Alpine.directive('sortable', (el, { expression }, { evaluate, cleanup }) => {
        const sortable = Sortable.create(el, {
            group: el.dataset.sortableGroup ?? 'sortable',
            animation: 150,
            ghostClass: 'opacity-40',
            onAdd: (event) => {
                const id = event.item.dataset.sortableId;
                if (!id) return;

                const target = el.dataset.sortableTarget ?? 'null';

                // `$wire.` is not optional: Livewire exposes the component to Alpine as the
                // $wire magic and does NOT put component methods in Alpine's scope, so a bare
                // `place(...)` throws "place is not defined" — which Alpine swallows, leaving a
                // drag that silently does nothing.
                //
                // Livewire re-renders from server state right after, repainting the card
                // wherever the server says it belongs.
                evaluate(`$wire.${expression}(${id}, ${target})`);
            },
        });

        cleanup(() => sortable.destroy());
    });

    /**
     * Reorder rows within one list and persist the new order.
     *
     * Only the grip icon drags (`handle`), because each row *contains* another sortable list —
     * without that, grabbing a person card would pick up the whole row. Reads `el.children`
     * rather than a query selector for the same reason: nested cards must not be collected.
     */
    Alpine.directive('sortable-order', (el, { expression }, { evaluate, cleanup }) => {
        const sortable = Sortable.create(el, {
            animation: 150,
            handle: '[data-drag-handle]',
            draggable: '[data-slot-id]',
            ghostClass: 'opacity-40',
            onEnd: () => {
                const ids = Array.from(el.children)
                    .map((node) => node.dataset.slotId)
                    .filter(Boolean);

                // `$wire.` prefix required — see the note in x-sortable above.
                if (ids.length) evaluate(`$wire.${expression}([${ids.join(',')}])`);
            },
        });

        cleanup(() => sortable.destroy());
    });

    /**
     * 24-hour time field. `<input type="time">` renders AM/PM purely from the OS locale and
     * there is no HTML attribute to force 24-hour, so the picker has to own the format.
     */
    const timeFieldOptions = (initial) => ({
        enableTime: true,
        noCalendar: true,
        dateFormat: 'H:i',
        time_24hr: true,
        minuteIncrement: 15,
        defaultDate: initial,
    });

    /** Renders the quick-pick row into an open flatpickr time popup. */
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

    /** Writes into a Livewire property — for a field that is part of a form. */
    Alpine.data('timePicker', (property, initial = null, quickTimes = []) => ({
        init() {
            if (typeof window.flatpickr !== 'function') return;

            this.picker = window.flatpickr(this.$refs.input, {
                ...timeFieldOptions(initial),
                // Third arg false: don't re-render the component on every keystroke.
                onChange: (dates, value) => this.$wire.set(property, value, false),
                onReady: (_selectedDates, _dateStr, fp) => addQuickTimes(fp, quickTimes, (time) => {
                    fp.setDate(time, true);
                    fp.close();
                }),
            });
        },
        clear() {
            this.picker?.clear();
            this.$wire.set(property, null, false);
        },
    }));

    /**
     * Saves straight to one existing slot — there is no form around it, the change *is* the
     * submit. Separate from timePicker because that one fills in a property to be submitted
     * later, while this one persists on change.
     */
    Alpine.data('slotTimePicker', (slotId, initial = null, quickTimes = []) => ({
        init() {
            if (typeof window.flatpickr !== 'function') return;

            window.flatpickr(this.$refs.input, {
                ...timeFieldOptions(initial),
                onChange: (dates, value) => this.$wire.updateSlotTime(slotId, value || null),
                onReady: (_selectedDates, _dateStr, fp) => addQuickTimes(fp, quickTimes, (time) => {
                    fp.setDate(time, true);
                    fp.close();
                }),
            });
        },
    }));

    /**
     * The editable "Časy nástupu" list on the team settings page. Plain array in Alpine state,
     * serialized to `quick_times[]` hidden inputs so it rides along with the rest of that
     * page's normal (non-Livewire) form submit.
     */
    Alpine.data('quickTimesEditor', (initial = []) => ({
        times: [...initial],
        newTime: '',
        picker: null,
        init() {
            if (typeof window.flatpickr !== 'function') return;

            this.picker = window.flatpickr(this.$refs.newTimeInput, {
                ...timeFieldOptions(null),
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
    }));

    /** Jump straight to a week instead of clicking the arrows repeatedly. */
    Alpine.data('weekJump', (current, urlTemplate) => ({
        init() {
            if (typeof window.flatpickr !== 'function') return;

            window.flatpickr(this.$refs.input, {
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
            this.$refs.input._flatpickr?.open();
        },
    }));

    Alpine.data('absenceRangePicker', (dateFrom, dateTo, openModal = false) => ({
        openModal,
        dateFrom,
        dateTo,
        initFlatpickr() {
            if (typeof window.flatpickr !== 'function') return;
            window.flatpickr(this.$refs.rangeInput, {
                mode: 'range',
                dateFormat: 'Y-m-d',
                altInput: true,
                altInputClass: 'w-full rounded-lg border border-neutral-700 bg-neutral-800 px-3.5 py-2.5 text-sm text-neutral-100 placeholder-neutral-500 focus:border-neutral-500 focus:outline-none cursor-pointer',
                altFormat: 'j. n. Y',
                defaultDate: [this.dateFrom, this.dateTo],
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

function showToast(message, type = 'success', icon = '') {
    window.dispatchEvent(new CustomEvent('toast', {
        detail: { message, type, icon }
    }));
}

document.addEventListener('change', function (event) {
    if (event.target.id === 'names_checkbox') {
        document.documentElement.classList.toggle('hide-names', !event.target.checked);
    }
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
                label: 'Odpracované dni',
                data: data,
                backgroundColor: 'rgba(99, 102, 241, 0.85)',
                hoverBackgroundColor: 'rgba(129, 140, 248, 1)',
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
                        color: '#737373',
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

    const element = document.getElementById('my-dropzone');

    if (element) {
        const myDropzone = new Dropzone("#my-dropzone", {
            url: window.appRoutes.fileUpload,
            paramName: "file",
            maxFilesize: 2,
        });

        myDropzone.on('queuecomplete', function () {
            showToast('Nahrávanie dokončené.')
            setTimeout(function () {
                window.location.search += '&show=files';
            }, 1000);
        });
    }
});

// Welcome-page auditorium. Dynamic import so the canvas code is its own chunk and only the
// one page that renders a [data-seat-field] ever downloads it.
const seatFieldCanvas = document.querySelector('[data-seat-field]');
if (seatFieldCanvas) {
    import('./seat-field.js').then(({ mountSeatField }) => mountSeatField(seatFieldCanvas));
}
