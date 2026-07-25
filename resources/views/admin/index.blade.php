<x-layout title="POUŽÍVATELIA">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-5">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-bold text-neutral-100">Správa používateľov</h1>
            <button data-modal-target="default-modal" data-modal-toggle="default-modal"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg text-neutral-900 bg-neutral-100 hover:bg-white transition focus:outline-none shadow"
                    type="button">
                <i class="fa-solid fa-user-plus text-xs" aria-hidden="true"></i> Pridať používateľa
            </button>
        </div>

        <div id="default-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
            <div class="relative p-4 w-full max-w-2xl max-h-full">
                <!-- Modal content -->
                <div class="relative rounded-xl shadow-2xl bg-neutral-900 border border-neutral-800">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-neutral-800">
                        <h3 class="text-lg font-bold text-white">
                            Pridať používateľa
                        </h3>
                        <button type="button" class="text-neutral-400 bg-transparent rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center hover:bg-neutral-800 hover:text-white" data-modal-hide="default-modal">
                            <i class="fa-solid fa-xmark text-lg"></i>
                            <span class="sr-only">Zavrieť okno</span>
                        </button>
                    </div>
                    <!-- Modal body -->
                    <div class="p-4 md:p-5 space-y-4">
                        @include('admin.partials._createuser')
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <livewire:users-data-table />
        </div>
    </div>
</x-layout>
