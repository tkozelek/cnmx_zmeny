<div id="timeModal"
     class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center hidden z-50 p-4">
    <div class="bg-gray-800 rounded-xl shadow-2xl p-8 w-full max-w-md relative">
        <div class="flex items-center justify-between mb-5 mt-5">
            <button id="prevDayBtn"
                    class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </button>
            <h3 id="modalDate" class="text-2xl font-bold text-white"></h3>
            <button id="nextDayBtn"
                    class="p-2 rounded-full hover:bg-gray-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
        <div class="space-y-6 mt-5">
            <div>
                <label for="startTime" class="block text-sm font-medium text-gray-400 mb-1">Začiatok
                    práce</label>
                <input type="time" id="startTime"
                       class="w-full p-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label for="endTime" class="block text-sm font-medium text-gray-400 mb-1">Koniec
                    práce</label>
                <input type="time" id="endTime"
                       class="w-full p-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="flex flex-wrap flex-grow gap-5">
                <label class="inline-flex items-center cursor-pointer">
                    <input type="checkbox" value="" class="sr-only peer" id="breakToggle">
                    <div
                        class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600 dark:peer-checked:bg-blue-600"></div>
                    <span class="ms-3 text-sm font-medium text-gray-300">Prestávka</span>
                </label>
                <input type="number" min="0" id="breakTime"
                       class="p-3 bg-gray-700 border border-gray-600 text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

        </div>
        <div class="flex justify-between mt-8">
            <button id="deleteTime"
                    class="w-full py-3 mx-2 px-4 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 transition-colors">
                Vymazať
            </button>
            <button id="saveTime"
                    class="w-full py-3 mx-2 px-4 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                Uložiť
            </button>
        </div>
        <button id="closeModal"
                class="absolute top-4 right-4 text-gray-400 hover:text-white">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                 stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>
</div>
