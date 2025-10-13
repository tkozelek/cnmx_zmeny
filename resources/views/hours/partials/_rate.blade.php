<section class="mb-8 bg-gray-800 p-6 rounded-xl shadow-md">
    <h2 class="text-xl font-semibold mb-2 text-gray-300">Hodinové Sadzby (€/hod)</h2>
    <div class="flex flex-wrap justify-center items-center gap-6">
        <div class="grow">
            <x-form-input
                type="number"
                name="weekdayRate"
                id="weekdayRate"
                label="Pracovný deň"
                placeholder="napr. 5"
            />
        </div>
        <div class="grow">
            <x-form-input
                type="number"
                name="saturdayRate"
                id="saturdayRate"
                label="Sobota"
                placeholder="napr. 7"
            />
        </div>
        <div class="grow">
            <x-form-input
                type="number"
                name="sundayRate"
                id="sundayRate"
                label="Nedeľa"
                placeholder="napr. 10"
            />
        </div>
        <div class="grow">
            <x-form-input
                type="number"
                name="breakTime"
                id="breakTimeDefault"
                label="Prestávka (v min)"
                placeholder="napr. 30"
            />
        </div>
    </div>
</section><?php
