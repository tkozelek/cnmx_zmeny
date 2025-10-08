<div class="flex items-center justify-center my-2 mb-4 ">
    <div class="mt-2 mb-2 float-right">
        <button data-modal-target="default-modal" data-modal-toggle="default-modal" class="bg-blue-700 hover:bg-blue-900 text-white font-bold px-3 p-2 rounded" type="button">
            <i class="fa-solid fa-file"></i> Súbory
        </button>

        
        <!-- SUBORY MODAL -->
        <div>
            <div id="default-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
                <div class="relative p-4 max-w-8xl w-full max-h-full">
                    <!-- Modal content -->
                    <div class="relative rounded-lg shadow bg-gray-700">
                        <!-- Modal header -->
                        <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-600">
                            <h3 class="text-xl font-semibold text-white">
                                Súbory
                            </h3>
                            <button type="button" class="end-2.5 text-gray-400 bg-transparent rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center hover:bg-gray-600 hover:text-white" data-modal-hide="default-modal">
                                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                                </svg>
                                <span class="sr-only">Zavrieť okno</span>
                            </button>
                        </div>
                        <!-- Modal body -->
                        <div class="px-4 pb-4">
                            @include('partials._fileupload')
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
    </div>

</div>
