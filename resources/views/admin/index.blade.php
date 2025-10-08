<x-layout>
    <div class="md:container mx-auto px-4 sm:px-6 lg:px-8 py-5">

        <div class="flex justify-between items-center">
            <div></div>
            <button data-modal-target="default-modal" data-modal-toggle="default-modal"
                    class="inline-flex items-center px-5 py-2 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-500 hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-slate-900 focus:ring-indigo-500 transition-colors duration-150"
                    type="button">
                <i class="fa-solid fa-plus mr-2 mt-1.5 h-5 w-5" aria-hidden="true"></i> Pridať používateľa
            </button>
        </div>
        <div id="default-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-2xl max-h-full">
                <!-- Modal content -->
                <div class="relative rounded-lg shadow bg-gray-700">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-600">
                        <h3 class="text-xl font-semibold text-white">
                            Pridať používateľa
                        </h3>
                        <button type="button" class="text-gray-400 bg-transparent rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center hover:bg-gray-600 hover:text-white" data-modal-hide="default-modal">
                            <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                            </svg>
                            <span class="sr-only">Close modal</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 !pt-0.5 md:p-5 space-y-4">
                        @include('admin.partials._createuser')
                    </div>
                    <!-- Modal footer -->
                </div>
            </div>
        </div>
    </div>
    <!-- LIVEWIRE -->
    <div>
        <livewire:user-table :roles="$roles"/>
    </div>

</x-layout>
