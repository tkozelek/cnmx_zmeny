<div class="mt-8 bg-gray-800 p-6 rounded-xl shadow-md">
    <h2 class="text-xl font-semibold mb-4 text-gray-300">Mesačný Súhrn</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-center">
        <x-summary_card
            color="blue"
            id="totalHours"
        >
            Celkovo hodín
        </x-summary_card>
        <x-summary_card color="green" id="totalEarnings">
            Celkový zárobok (odhad)
        </x-summary_card>
    </div>

    <div class="flex flex-grow items-center justify-center gap-5 mt-5 text-center">
        <x-summary_card color="blue" id="totalHoursWeekday" class="w-full">
            V týždni
        </x-summary_card>
        <x-summary_card color="blue" id="totalHoursSaturday" class="w-full">
            V sobotu
        </x-summary_card>
        <x-summary_card color="blue" id="totalHoursSunday" class="w-full">
            V nedeľu
        </x-summary_card>
    </div>

    <div class="flex flex-grow items-center justify-center gap-5 mt-5 text-center">
        <x-summary_card color="green" id="totalEarningsWeekday" class="w-full">
            V týždni
        </x-summary_card>
        <x-summary_card color="green" id="totalEarningsSaturday" class="w-full">
            V sobotu
        </x-summary_card>
        <x-summary_card color="green" id="totalEarningsSunday" class="w-full">
            V nedeľu
        </x-summary_card>
    </div>

    <div class="flex flex-grow items-center justify-center gap-5 mt-5 text-center">
        <x-summary_card color="blue" id="breakHours" class="w-full">
            Prestávky
        </x-summary_card>
    </div>
</div>
