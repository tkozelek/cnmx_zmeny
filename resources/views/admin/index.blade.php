<x-layout title="Správa používateľov" description="Zoznam a správa používateľov vášho kina.">
    <div x-data="{ openModal: @js($errors->any()) }" class="container mx-auto px-4 sm:px-6 lg:px-8 py-5">
        <x-page-header icon="fa-users" title="Správa používateľov" subtitle="Zoznam a správa používateľov tohto kina.">
            <button @click="openModal = true"
                    aria-haspopup="dialog"
                    :aria-expanded="openModal.toString()"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-lg text-neutral-900 bg-neutral-100 hover:bg-white transition focus:outline-none shadow"
                    type="button">
                <i class="fa-solid fa-user-plus text-xs" aria-hidden="true"></i> Pridať používateľa
            </button>
        </x-page-header>

        <template x-teleport="body">
            <div
                x-cloak
                x-show="openModal"
                @keydown.escape.window="openModal = false"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4 bg-black/80 backdrop-blur-md overflow-y-auto"
            >
                <div @click.away="openModal = false"
                     role="dialog"
                     aria-modal="true"
                     aria-labelledby="modal-adduser-title"
                     class="relative w-full max-w-2xl rounded-xl border border-neutral-800 bg-neutral-900 shadow-2xl overflow-hidden my-auto">
                    <div class="flex items-center justify-between p-4 md:p-5 border-b border-neutral-800">
                        <h3 id="modal-adduser-title" class="text-lg font-bold text-white">
                            Pridať používateľa
                        </h3>
                        <button @click="openModal = false" type="button" aria-label="Zatvoriť okno" class="text-neutral-400 bg-transparent rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center hover:bg-neutral-800 hover:text-white">
                            <i class="fa-solid fa-xmark text-lg"></i>
                            <span class="sr-only">Zavrieť okno</span>
                        </button>
                    </div>
                    <div class="p-4 md:p-5 space-y-4">
                        @include('admin.partials._createuser')
                    </div>
                </div>
            </div>
        </template>

        <div class="mt-4">
            <livewire:users-data-table />
        </div>
    </div>
</x-layout>
