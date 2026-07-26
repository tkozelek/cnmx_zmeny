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
