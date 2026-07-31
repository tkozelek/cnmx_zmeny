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

    /** Writes into a Livewire property — for a field that is part of a form. */
    Alpine.data('timePicker', (property, initial = null) => ({
        init() {
            if (typeof window.flatpickr !== 'function') return;

            this.picker = window.flatpickr(this.$refs.input, {
                ...timeFieldOptions(initial),
                // Third arg false: don't re-render the component on every keystroke.
                onChange: (dates, value) => this.$wire.set(property, value, false),
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
    Alpine.data('slotTimePicker', (slotId, initial = null) => ({
        init() {
            if (typeof window.flatpickr !== 'function') return;

            window.flatpickr(this.$refs.input, {
                ...timeFieldOptions(initial),
                onChange: (dates, value) => this.$wire.updateSlotTime(slotId, value || null),
            });
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

document.addEventListener('DOMContentLoaded', function() {
    Chart.register(...registerables);

    var canvas = document.getElementById('barChart');
    if (canvas) {
        var ctx = canvas.getContext('2d');

        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Pondelok', 'Utorok', 'Streda', 'Štvrtok', 'Piatok', 'Sobota', 'Nedeľa'],
                datasets: [{
                    label: 'Počty dni',
                    data: chartData,
                    backgroundColor: 'rgba(5,19,183,0.2)',
                    borderColor: 'rgb(21,26,155)',
                    borderWidth: 1
                }]
            },
            options: {
                plugins: {
                    title: {
                        display: true,
                        text: 'Frekvencia zapisovanych dni',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0,
                        },
                        grace: '5%',
                    }
                },
                elements: {
                    bar: {
                        backgroundColor: '#ea3308'
                    }
                },
                responsive: true
            }
        });
    }

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
