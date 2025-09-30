<button
    class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-3 p-2 rounded-md transition-colors duration-150"
    type="button"
    x-data=""
    x-on:click.prevent="$dispatch('open-modal', 'files-modal')"
>
    <i class="fa-solid fa-folder-open"></i>
    <span class="ml-1 font-medium">Súbory</span>
</button>

<x-modal
    name="files-modal"
    :show="request()->query('show') === 'files'"
    maxWidth="6xl"
    focusable
>
    <div class="rounded-lg shadow bg-gray-700">
        <div class="flex items-center justify-between p-4 md:p-5 border-b rounded-t border-gray-600">
            <h3 class="text-xl font-semibold text-white">
                Súbory
            </h3>
            <button type="button" class="end-2.5 text-gray-400 bg-transparent rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center hover:bg-gray-600 hover:text-white"
                    x-on:click="$dispatch('close')"
            >
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
                <span class="sr-only">Zavrieť okno</span>
            </button>
        </div>
        <div class="px-4 pb-4">
            @include('partials._fileupload')
        </div>
    </div>
</x-modal>
