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
        this.view.bindSaveRatesButton(this.handleSaveRates.bind(this));
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

        const originalText = "Uložiť mesiac";
        this.view.saveMonthBtn.textContent = 'Ukladám...';
        this.view.saveMonthBtn.disabled = true;

        try {
            let month = this.currentDate.getMonth() + 1;
            let year = this.currentDate.getFullYear();

            const workDataForMonth = this.data.getAllEntries(month, year);
            const saveShifts = window.hoursRoutes.saveShifts;

            const data = {
                workData: workDataForMonth,
                month,
                year,
            };

            const result = await this.sendFetch(saveShifts, data);

            if (result.message === 'deleted') this.showToast('Mesiac zmazaný.');
            if (result.message === 'updated') this.showToast('Mesiac aktualizovaný.');
        } catch (error) {
            console.error(error);
        } finally {
            this.view.saveMonthBtn.textContent = originalText;
            this.view.saveMonthBtn.disabled = false;
            this.view.hideSaveButton();
        }
    }

    async handleSaveRates(e) {
        if (parseInt(this.view.sameUser.value) !== 1) {
            this.showToast('Uložiť môžeš iba svoje hodiny.', 'error');
            throw new Error(`Nemožno uložiť cudzie hodiny.`);
        }
        e.preventDefault();

        const originalText = "Uložiť";
        this.view.saveRatesBtn.textContent = 'Ukladám...';
        this.view.saveRatesBtn.disabled = true;

        try {
            const saveRates = window.hoursRoutes.saveRates;
            const data = {
                weekday: this.view.weekdayRateInput.value,
                saturday: this.view.saturdayRateInput.value,
                sunday: this.view.sundayRateInput.value,
                break: this.view.breakTimeDefaultInput.value
            };

            const result = await this.sendFetch(saveRates, data);

            if (result.message === "rates_updated") this.showToast('Hodinové sadzby upravené.');
        } catch (error) {
            console.log(error);
        } finally {
            this.view.saveRatesBtn.textContent = "Uložené..";
            setTimeout(() => {
                this.view.saveRatesBtn.textContent = originalText;
                this.view.saveRatesBtn.disabled = false;
                this.view.hideRatesButton();
            }, 1500);
        }
    }

    async sendFetch(url, data) {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const response = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data),
        });

        if (!response.ok) {
            this.showToast('Nastala chyba pri aktualizácií.', 'error');
            throw new Error(`Response status: ${response.status}`);
        }
        return await response.json();
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
            break: parseFloat(this.view.breakTimeDefaultInput.value) || 0,
        };
        this.view.showRatesButton();
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
        if (parseInt(this.view.sameUser.value) !== 1) {
            this.data.clearWorkData();
        }

        if (window.rates && window.rates.length !== 0) {
            const rates = window.rates;
            this.data.updateRates(rates);
        }

        if (window.workdata && window.workdata.length !== 0) {
            const workdata = window.workdata;
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

