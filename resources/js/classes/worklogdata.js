export default class WorkLogData {
    constructor() {
        this.workData = {};
        this.rates = {};
        this.load();
    }

    // LOAD A SAVE
    load() {
        const savedWorkData = localStorage.getItem('workData');
        if (savedWorkData) {
            this.workData = JSON.parse(savedWorkData);
        }

        const savedRates = localStorage.getItem('rates');
        if (savedRates) {
            this.rates = JSON.parse(savedRates);
        }
    }

    saveWorkData() {
        localStorage.setItem('workData', JSON.stringify(this.workData));
    }

    saveRates() {
        localStorage.setItem('rates', JSON.stringify(this.rates));
    }

    //  UTILS
    static splitDateKey(dateKey) {
        const [year, month, day] = dateKey.split('-');
        return { monthKey: `${month}-${year}`, dayKey: day };
    }

    //  DATA
    getEntry(dateKey) {
        const { monthKey, dayKey } = WorkLogData.splitDateKey(dateKey);
        return this.workData[monthKey]?.[dayKey] || null;
    }

    getAllEntries(month, year) {
        const monthKey = `${String(month).padStart(2, '0')}-${year}`;
        return this.workData[monthKey] || {};
    }

    updateEntry(dateKey, data) {
        if (!data || !data.start || !data.end) return this.deleteEntry(dateKey);
        const { monthKey, dayKey } = WorkLogData.splitDateKey(dateKey);
        if (!this.workData[monthKey]) this.workData[monthKey] = {};
        this.workData[monthKey][dayKey] = data;
        this.saveWorkData();
    }

    deleteEntry(dateKey) {
        const { monthKey, dayKey } = WorkLogData.splitDateKey(dateKey);
        if (this.workData[monthKey]) {
            delete this.workData[monthKey][dayKey];
            if (Object.keys(this.workData[monthKey]).length === 0) {
                delete this.workData[monthKey]; // clean up empty months
            }
            this.saveWorkData();
        }
    }

    clearWorkData() {
        this.workData = [];
        localStorage.removeItem('workData');

        this.rates = [];
        localStorage.removeItem('rates');
    }

    // RATES
    getRates() {
        return this.rates;
    }

    updateRates(newRates) {
        this.rates = { ...this.rates, ...newRates };
        this.saveRates();
    }
}

window.WorkLoadData = WorkLogData;
