<div class="bg-neutral-900 p-4 md:p-6 rounded-lg shadow-xl text-neutral-200">
    @if(auth()->user()->hasRole('admin'))
        <div class="w-full mb-8">
            <form action="{{ route('media.store') }}" method="post"
                  class="dropzone bg-neutral-950 rounded-xl border-2 border-dashed border-neutral-800 hover:border-neutral-600 transition-all duration-300 p-6 text-center"
                  id="my-dropzone">
                @csrf
                <input type="hidden" name="week_start" value="{{ $weekStart->toDateString() }}">
                <div class="dz-message">
                    <div class="flex flex-col items-center justify-center">
                        <i class="fa-solid fa-cloud-arrow-up text-4xl text-neutral-500 mb-4"></i>
                        <span class="block text-lg font-medium text-neutral-300">Sem presuňte súbory, alebo kliknite pre nahratie.</span>
                        <span class="block text-sm text-neutral-500 mt-1">Maximálna veľkosť súboru: 2MB</span>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <div class="w-full">
        @if($media->isNotEmpty())
            <h2 class="text-xl font-semibold mb-4 text-neutral-100">Nahrané súbory</h2>
            <div class="space-y-3">
                @foreach($media as $file)
                    <div div-file-id="{{ $file->id }}"
                         class="flex flex-col md:flex-row md:items-center md:justify-between bg-neutral-800/80 p-4 rounded-lg shadow-md border border-neutral-800 hover:border-neutral-700 transition-all">
                        <div class="flex items-center gap-3 max-w-lg min-w-0 mb-3 md:mb-0 md:w-5/12 lg:w-6/12 xl:w-7/12 truncate">
                            <i class="fa-solid fa-file text-xl text-neutral-400"></i>
                            <span class="truncate whitespace-nowrap font-medium text-neutral-200"
                                  title="{{ $file->original_name }}">{{ $file->original_name }}</span>
                        </div>

                        <div class="flex items-center justify-start md:justify-end gap-2 md:w-4/12 lg:w-4/12 xl:w-3/12">
                            @if($file->isImage())
                                <button
                                    data-image="{{ route('media.download', $file) }}"
                                    x-on:click.prevent="$dispatch('open-modal', 'imageModal')"
                                    class="open-image-modal flex items-center justify-center p-2 bg-amber-600/20 text-amber-300 hover:bg-amber-600/30 rounded-md transition-colors"
                                    title="Otvoriť">
                                    <i class="fa-solid fa-eye"></i>
                                    <span class="ml-2 hidden sm:inline">Otvoriť</span>
                                </button>
                            @endif

                            <a href="{{ route('media.download', $file) }}"
                               class="flex items-center justify-center p-2 bg-emerald-600/20 text-emerald-300 hover:bg-emerald-600/30 rounded-md transition-colors"
                               title="Stiahnuť">
                                <i class="fa-solid fa-download"></i>
                                <span class="ml-2 hidden sm:inline">Stiahnuť</span>
                            </a>

                            @if(auth()->user()->hasRole('admin'))
                                <form method="POST" action="{{ route('media.visibility', $file) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="flex items-center justify-center p-2 {{ $file->is_visible ? 'bg-amber-500/20 text-amber-300 hover:bg-amber-500/30' : 'bg-neutral-700 text-neutral-200 hover:bg-neutral-600' }} rounded-md transition-colors"
                                            title="{{ $file->is_visible ? 'Skryť' : 'Zobraziť' }}">
                                        <i class="fa-solid fa-eye{{ $file->is_visible ? '-slash' : '' }}"></i>
                                        <span class="ml-2 hidden sm:inline">{{ $file->is_visible ? 'Skryť' : 'Zverejniť' }}</span>
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('media.destroy', $file) }}"
                                      onsubmit="return confirm('Určite zmazať súbor {{ $file->original_name }}?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="flex items-center justify-center p-2 bg-rose-600/20 text-rose-300 hover:bg-rose-600/30 rounded-md transition-colors"
                                            title="Odstrániť">
                                        <i class="fa-solid fa-trash"></i>
                                        <span class="ml-2 hidden sm:inline">Zmazať</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-10 bg-neutral-950 rounded-lg border border-neutral-800">
                <i class="fa-solid fa-folder-open text-5xl text-neutral-600 mb-4"></i>
                <h3 class="text-xl font-medium text-neutral-400">Žiadne súbory neboli pridané.</h3>
                @if(auth()->user()->hasRole('admin'))
                    <p class="text-neutral-500 mt-2">Súbory môžete pridať pomocou formulára vyššie.</p>
                @endif
            </div>
        @endif
    </div>
</div>

<x-modal name="imageModal" maxWidth="6xl" z="99" focusable>
    <div class="flex justify-between rounded-t-lg bg-neutral-900 border-b border-neutral-800">
        <button type="button"
                class="text-neutral-400 bg-transparent rounded-lg text-sm p-3 ml-auto inline-flex items-center hover:bg-neutral-800 hover:text-white"
                x-on:click="$dispatch('close')">
            <i class="fa fa-x"></i>
            <span class="sr-only">Zavrieť okno</span>
        </button>
    </div>
    <div class="flex justify-center p-10 bg-neutral-950">
        <img id="modalImage" src="" alt="Náhľad" class="max-w-full rounded-lg"/>
    </div>
</x-modal>

<script>
    document.querySelectorAll('.open-image-modal').forEach(button => {
        button.addEventListener('click', () => {
            document.getElementById('modalImage').src = button.getAttribute('data-image');
        });
    });

    window.appRoutes = Object.assign(window.appRoutes || {}, {
        fileUpload: @json(route('media.store')),
    });
</script>
