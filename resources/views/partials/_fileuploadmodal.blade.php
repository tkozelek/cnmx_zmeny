@php
    $canManageMedia = auth()->user()?->can('create', \App\Models\Media::class);
    $hasVisibleMedia = isset($media) && $media->contains('is_visible', true);
@endphp

@if($hasVisibleMedia || $canManageMedia)
    <button
        class="inline-flex min-h-10 items-center gap-2 rounded-md bg-neutral-900 px-4 text-sm font-medium text-neutral-300 ring-1 ring-inset ring-neutral-800 transition hover:bg-neutral-800 hover:text-white focus:outline-none focus:ring-2 focus:ring-neutral-500"
        type="button"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'files-modal')"
    >
        <i class="fa-solid fa-folder-open"></i>
        Súbory
    </button>

    <x-modal
        name="files-modal"
        :show="request()->query('show') === 'files'"
        maxWidth="6xl"
        focusable
    >
        <div class="rounded-lg shadow bg-neutral-900 border border-neutral-800">
            <div class="flex items-center justify-between p-4 md:p-5 border-b border-neutral-800">
                <h3 class="text-xl font-semibold text-neutral-100">
                    Súbory
                </h3>
                <button type="button" class="end-2.5 text-neutral-400 bg-transparent rounded-lg text-sm w-8 h-8 ms-auto inline-flex justify-center items-center hover:bg-neutral-800 hover:text-white"
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
@endif
