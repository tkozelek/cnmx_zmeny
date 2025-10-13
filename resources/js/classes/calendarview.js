export default class CalendarView {
    constructor() {
        this.calendarGrid = document.getElementById("calendar-grid");
        this.calendarHeader = document.getElementById("calendar-header");
        this.monthAndYear = document.getElementById("monthAndYear");
        this.modal = document.getElementById("timeModal");
        this.modalDate = document.getElementById("modalDate");
        this.deleteTimeBtn = document.getElementById("deleteTime");
        this.startTimeInput = document.getElementById("startTime");
        this.endTimeInput = document.getElementById("endTime");
        this.breakToggleInput = document.getElementById("breakToggle");
        this.breakTimeInput = document.getElementById("breakTime");
        this.breakTimeDefaultInput = document.getElementById("breakTimeDefault");

        // Rate inputs
        this.weekdayRateInput = document.getElementById("weekdayRate");
        this.saturdayRateInput = document.getElementById("saturdayRate");
        this.sundayRateInput = document.getElementById("sundayRate");

        // Summary elements
        this.totalHoursEl = document.getElementById("totalHours");
        this.totalEarningsEl = document.getElementById("totalEarnings");
        this.totalHoursWeekdayEl = document.getElementById("totalHoursWeekday");
        this.totalHoursSaturdayEl = document.getElementById("totalHoursSaturday");
        this.totalHoursSundayEl = document.getElementById("totalHoursSunday");
        this.totalEarningsWeekdayEl = document.getElementById(
            "totalEarningsWeekday"
        );
        this.totalEarningsSaturdayEl = document.getElementById(
            "totalEarningsSaturday"
        );
        this.totalEarningsSundayEl = document.getElementById("totalEarningsSunday");
        this.breakHoursEl = document.getElementById("breakHours");

        // Buttons
        this.prevMonthBtn = document.getElementById("prevMonth");
        this.nextMonthBtn = document.getElementById("nextMonth");
        this.closeModalBtn = document.getElementById("closeModal");
        this.saveTimeBtn = document.getElementById("saveTime");
        this.prevDayBtn = document.getElementById("prevDayBtn");
        this.nextDayBtn = document.getElementById("nextDayBtn");
        this.saveMonthBtn = document.getElementById("saveMonthButton");

        this.MONTH_NAMES = [
            "Január",
            "Február",
            "Marec",
            "Apríl",
            "Máj",
            "Jún",
            "Júl",
            "August",
            "September",
            "Október",
            "November",
            "December",
        ];
        this.DAY_NAMES = ["Po", "Ut", "St", "Št", "Pi", "So", "Ne"];
    }

    render(date, workData, calculateHours, handleDayClick) {
        const year = date.getFullYear();
        const month = date.getMonth();
        this.monthAndYear.textContent = `${this.MONTH_NAMES[month]} ${year}`;
        this.calendarGrid.innerHTML = "";
        this.renderDayNames();

        const firstDayOfMonth = new Date(year, month, 1).getDay();
        const startingDay = firstDayOfMonth === 0 ? 6 : firstDayOfMonth - 1;
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        for (let i = 0; i < startingDay; i++) {
            this.calendarGrid.appendChild(document.createElement("div"));
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateKey = `${year}-${String(month + 1).padStart(2, "0")}-${String(
                day
            ).padStart(2, "0")}`;
            const dayKey = String(day).padStart(2, "0");
            const dayCell = document.createElement("div");

            dayCell.className =
                "relative p-2 h-24 border border-gray-500 rounded-lg cursor-pointer hover:bg-gray-700/50 transition-colors flex flex-col justify-start items-center";
            dayCell.dataset.dateKey = dateKey;

            dayCell.innerHTML = `<div class="font-bold">${day}</div>`;

            const hoursInfo = document.createElement("div");
            hoursInfo.className =
                "text-[0.7rem] md:text-sm mt-1 text-gray-400";

            const breakInfo = document.createElement("div");
            breakInfo.className = "text-[0.6rem] md:text-sm mt-0.5 text-red-500";

            const entry = workData[dayKey];

            if (entry && entry.start && entry.end) {
                const hours = calculateHours(entry.start, entry.end);
                hoursInfo.textContent = `${hours.toFixed(2)} hod`;
                dayCell.classList.add("bg-blue-900/50");
                if (entry.breakTime && entry.breakTime !== 0) {
                    breakInfo.textContent = `-${entry.breakTime}min`;
                }
            }

            dayCell.appendChild(hoursInfo);
            dayCell.appendChild(breakInfo);

            const today = new Date();
            if (
                day === today.getDate() &&
                month === today.getMonth() &&
                year === today.getFullYear()
            ) {
                dayCell.classList.add("bg-yellow-800/30");
                dayCell
                    .querySelector(".font-bold")
                    .classList.add("text-yellow-300");
            }

            dayCell.addEventListener("click", () => handleDayClick(dateKey));
            this.calendarGrid.appendChild(dayCell);
        }
    }

    renderDayNames() {
        this.calendarHeader.innerHTML = this.DAY_NAMES.map(
            (day) => `<div>${day}</div>`
        ).join("");
    }

    updateSummary(summary) {
        this.totalHoursEl.textContent = summary.totalHours.toFixed(2);
        this.breakHoursEl.textContent = `${(summary.totalBreak / 60).toFixed(
            2
        )} hod`;
        this.totalEarningsEl.textContent = `${summary.totalEarnings.toFixed(2)} €`;

        this.totalHoursWeekdayEl.textContent = `${summary.weekdayHours.toFixed(
            2
        )} hod`;
        this.totalHoursSaturdayEl.textContent = `${summary.saturdayHours.toFixed(
            2
        )} hod`;
        this.totalHoursSundayEl.textContent = `${summary.sundayHours.toFixed(
            2
        )} hod`;

        this.totalEarningsWeekdayEl.textContent = `${summary.weekdayEarnings.toFixed(
            2
        )} €`;
        this.totalEarningsSaturdayEl.textContent = `${summary.saturdayEarnings.toFixed(
            2
        )} €`;
        this.totalEarningsSundayEl.textContent = `${summary.sundayEarnings.toFixed(
            2
        )} €`;
    }

    updateRateInputs(rates) {
        this.weekdayRateInput.value = rates.weekday || "";
        this.saturdayRateInput.value = rates.saturday || "";
        this.sundayRateInput.value = rates.sunday || "";
        this.breakTimeDefaultInput.value = rates.breakTime || "";
    }

    openModal(dateKey, entry, rates) {
        const [year, month, day] = dateKey.split("-").map(Number);
        const dateObj = new Date(year, month - 1, day);
        this.modalDate.textContent = dateObj.toLocaleDateString("sk-SK", {
            weekday: "long",
            day: "numeric",
            month: "long",
        });

        const daysInCurrentMonth = new Date(year, month, 0).getDate();
        this.prevDayBtn.disabled = day === 1;
        this.nextDayBtn.disabled = day === daysInCurrentMonth;

        if (entry) {
            this.startTimeInput.value = entry.start || "";
            this.endTimeInput.value = entry.end || "";
            this.breakToggleInput.checked = entry.breakTime || entry.breakTime !== 0;
            this.breakTimeInput.value = entry.breakTime || rates.breakTime || "";
            this.deleteTimeBtn.classList.remove("hidden");
        } else {
            this.startTimeInput.value = "";
            this.endTimeInput.value = "";
            this.breakToggleInput.checked = false;
            this.breakTimeInput.value = rates.breakTime || "";
            this.deleteTimeBtn.classList.add("hidden");
        }

        this.modal.classList.remove("hidden");
        this.startTimeInput.focus();
    }

    closeModal() {
        this.modal.classList.add("hidden");
    }

    bindMonthChange(handler) {
        this.prevMonthBtn.addEventListener("click", () => handler(-1));
        this.nextMonthBtn.addEventListener("click", () => handler(1));
    }

    bindDayNavigate(handler) {
        this.prevDayBtn.addEventListener("click", () => handler(-1));
        this.nextDayBtn.addEventListener("click", () => handler(1));
    }

    bindTimeActions(handleSave, handleDelete) {
        this.saveTimeBtn.addEventListener("click", () => handleSave(true));
        this.deleteTimeBtn.addEventListener("click", () => handleDelete(true));
    }

    bindModalClose(handler) {
        this.closeModalBtn.addEventListener("click", handler);
        window.addEventListener("click", (event) => {
            if (event.target === this.modal) {
                handler();
            }
        });
    }

    bindRateChange(handler) {
        this.weekdayRateInput.addEventListener("input", () => handler());
        this.saturdayRateInput.addEventListener("input", () => handler());
        this.sundayRateInput.addEventListener("input", () => handler());
        this.breakTimeDefaultInput.addEventListener("input", () => handler());
    }

    bindAutoBreakToggle(handler) {
        this.startTimeInput.addEventListener("change", handler);
        this.endTimeInput.addEventListener("change", handler);
    }

    bindSaveButton(handler) {
        this.saveMonthBtn.addEventListener("click", handler);
    }

    showSaveButton() {
        this.saveMonthBtn.classList.remove("hidden");
    }
}

window.CalendarView = CalendarView;
