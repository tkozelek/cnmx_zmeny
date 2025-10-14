import WorkLogData from './worklogdata.js';
import CalendarView from './calendarview.js';

class WorkLogApp {
    constructor(data, view) {
        this.data = data;
        this.view = view;
        this.currentDate = new Date();
        this.selectedDateKey = null;
    }

    initialize() {
        this.checkForData();
        this.bindEvents();
        this.view.updateRateInputs(this.data.getRates());
        this.updateView();
    }

    bindEvents() {
        this.view.bindMonthChange(this.handleChangeMonth.bind(this));
        this.view.bindTimeActions(this.handleSaveTime.bind(this), this.handleDeleteTime.bind(this));
        this.view.bindModalClose(this.handleCloseModal.bind(this));
        this.view.bindDayNavigate(this.handleNavigateDay.bind(this));
        this.view.bindRateChange(this.handleRateChange.bind(this));
        this.view.bindAutoBreakToggle(this.handleAutoBreakToggle.bind(this));
        this.view.bindSaveButton(this.handleSaveData.bind(this));
    }

    updateView() {
        let month = this.currentDate.getMonth();
        let year = this.currentDate.getFullYear();
        this.view.render(
            this.currentDate,
            this.data.getAllEntries(month + 1, year),
            this.calculateHours,
            this.handleDayClick.bind(this)
        );
        const summary = this.calculateSummary();
        this.view.updateSummary(summary);
    }

    // --- EVENT HANDLERS ---

    async handleSaveData(e) {
        if (parseInt(this.view.sameUser.value) !== 1) {
            this.showToast('Uložiť môžeš iba svoje hodiny.', 'error');
            throw new Error(`Nemožno uložiť cudzie hodiny.`);
        }

        e.preventDefault();
        let month = this.currentDate.getMonth() + 1;
        let year = this.currentDate.getFullYear();

        const workDataForMonth = this.data.getAllEntries(month, year);

        const saveShifts = window.appRoutes.saveShifts;

        const response = await this.sendFetch(saveShifts, workDataForMonth, month, year);

        if (!response.ok) {
            this.showToast('Nastala chyba pri aktualizácií.', 'error');
            throw new Error(`Response status: ${response.status}`);
        }
        const result = await response.json();

        if (result.message === 'deleted') {
            this.showToast('Mesiac zmazaný.');
        }

        if (result.message === 'updated') {
            this.showToast('Mesiac aktualizovaný.');
        }
        this.view.hideSaveButton();
    }

    async sendFetch(url, workDataForMonth, month, year) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        return await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                workData: workDataForMonth,
                month: month,
                year: year,
            }),
        });
    }

    handleChangeMonth(direction) {
        this.currentDate.setMonth(this.currentDate.getMonth() + direction);
        this.updateView();
    }

    handleDayClick(dateKey) {
        this.selectedDateKey = dateKey;
        const entry = this.data.getEntry(dateKey);
        this.view.openModal(dateKey, entry, this.data.getRates());
    }

    handleNavigateDay(direction) {
        this.handleSaveTime(false); // Auto-save before navigating
        const [year, month, day] = this.selectedDateKey.split('-').map(Number);
        const newDay = day + direction;
        const newDateKey = `${year}-${String(month).padStart(2, '0')}-${String(newDay).padStart(2, '0')}`;
        this.handleDayClick(newDateKey);
    }

    handleSaveTime(shouldCloseModal = true) {
        if (!this.selectedDateKey) return;
        const dataToSave = {
            date: this.selectedDateKey,
            start: this.view.startTimeInput.value,
            end: this.view.endTimeInput.value,
            break: this.view.breakToggleInput.checked ? this.view.breakTimeInput.value : 0,
        };
        this.data.updateEntry(this.selectedDateKey, dataToSave);
        this.updateView();
        if (shouldCloseModal) {
            this.handleCloseModal();
        }
        this.view.showSaveButton();
    }

    handleDeleteTime(shouldCloseModal = true) {
        if (!this.selectedDateKey) return;
        this.data.deleteEntry(this.selectedDateKey);
        this.updateView();
        if (shouldCloseModal) {
            this.handleCloseModal();
        }
        this.view.showSaveButton();
    }

    handleCloseModal() {
        this.view.closeModal();
        this.selectedDateKey = null;
    }

    handleRateChange() {
        const newRates = {
            weekday: parseFloat(this.view.weekdayRateInput.value) || 0,
            saturday: parseFloat(this.view.saturdayRateInput.value) || 0,
            sunday: parseFloat(this.view.sundayRateInput.value) || 0,
            breakTime: parseFloat(this.view.breakTimeDefaultInput.value) || 0,
        };
        this.data.updateRates(newRates);
        this.updateView();
    }

    handleAutoBreakToggle() {
        const hours = this.calculateHours(this.view.startTimeInput.value, this.view.endTimeInput.value);
        if (hours > 6) {
            this.view.breakToggleInput.checked = true;
        }
    }

    // --- CALCULATIONS ---

    calculateHours(start, end, breakTime = 0) {
        if (!start || !end) return 0;
        const startTime = new Date(`1970-01-01T${start}:00`);
        const endTime = new Date(`1970-01-01T${end}:00`);
        let difference = endTime - startTime - (breakTime * 60 * 1000);

        if (difference < 0) { // Handle overnight shifts
            difference += 24 * 60 * 60 * 1000;
        }
        return difference / (1000 * 60 * 60);
    }

    calculateSummary() {
        let summary = {
            totalHours: 0, totalBreak: 0, totalEarnings: 0,
            weekdayHours: 0, saturdayHours: 0, sundayHours: 0,
            weekdayEarnings: 0, saturdayEarnings: 0, sundayEarnings: 0
        };

        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth() + 1;

        const workData = this.data.getAllEntries(month, year);
        const rates = this.data.getRates();

        for (const dateKey in workData) {
            const entry = workData[dateKey];

            const breakT = entry.breakTime ? parseFloat(entry.breakTime) || 0 : 0;
            const hours = this.calculateHours(entry.start, entry.end, breakT);

            summary.totalHours += hours;
            summary.totalBreak += breakT;

            const dayOfWeek = new Date(year, month - 1, Number(dateKey)).getDay();

            if (dayOfWeek === 0) { // Sunday
                summary.sundayHours += hours;
                summary.sundayEarnings += hours * (rates.sunday || 0);
            } else if (dayOfWeek === 6) { // Saturday
                summary.saturdayHours += hours;
                summary.saturdayEarnings += hours * (rates.saturday || 0);
            } else { // Weekday
                summary.weekdayHours += hours;
                summary.weekdayEarnings += hours * (rates.weekday || 0);
            }

        }
        summary.totalEarnings = summary.weekdayEarnings + summary.saturdayEarnings + summary.sundayEarnings;
        return summary;
    }

    showToast(message, type = 'success', icon = '') {
        window.dispatchEvent(new CustomEvent('toast', {
            detail: { message, type, icon }
        }));
    }

    checkForData() {
        this.data.clear();
        if (window.workdata && window.workdata.length !== 0) {
            const workdata = window.workdata;
            console.log(workdata);
            workdata.forEach((entry) => {
                const date = entry['date'];
                this.data.updateEntry(date, entry);
            });
        }
    }
}

window.WorkLoadApp = WorkLogApp;

// --- APPLICATION INITIALIZATION ---
document.addEventListener('DOMContentLoaded', function () {
    const app = new WorkLogApp(new WorkLogData(), new CalendarView());
    app.initialize();
});

