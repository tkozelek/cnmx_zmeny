<div class="bg-gray-800 p-4 md:p-6 rounded-lg shadow-xl text-white">
    @if(auth()->user()->hasRole(config('constants.roles.admin')))
        <div class="w-full mb-8">
            <form action="" method="post"
                  class="dropzone bg-gray-700 rounded-xl border-2 border-dashed border-gray-600 hover:border-blue-500 transition-all duration-300 p-6 text-center"
                  id="my-dropzone">
                @csrf
                <input type="hidden" name="id_week" value="{{ $week->id }}">
                <div class="dz-message">
                    <div class="flex flex-col items-center justify-center">
                        <i class="fa-solid fa-cloud-arrow-up text-4xl text-gray-400 mb-4"></i>
                        <span class="block text-lg font-medium text-gray-300">Sem presuňte súbory, alebo kliknite pre nahratie.</span>
                        <span class="block text-sm text-gray-500 mt-1">Maximálna veľkosť súboru: 2MB</span>
                    </div>
                </div>
            </form>
        </div>
    @endif

    <div class="w-full">
        @if(isset($files) && count($files) > 0)
            <h2 class="text-xl font-semibold mb-4 text-blue-400">Nahrané súbory</h2>
            <div class="space-y-3">
                @foreach($files as $file)
                    @php
                        $isImage = false;

                        $mimeType = $file->mime_type;

                        $imageMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/svg+xml'];

                        if (in_array($mimeType, $imageMimeTypes)) {
                            $isImage = true;
                        }
                    @endphp
                    <div
                        div-file-id="{{$file->id}}"
                        class="flex flex-col md:flex-row md:items-center md:justify-between bg-gray-700 p-4 rounded-lg shadow-md hover:shadow-lg transition-shadow duration-300">
                        <div
                            class="flex items-center gap-3 max-w-lg min-w-0 mb-3 md:mb-0 md:w-5/12 lg:w-6/12 xl:w-7/12 truncate"
                        >
                            <i class="fa-solid fa-file text-xl text-blue-400"></i>
                            <span class="truncate whitespace-nowrap font-medium text-gray-200"
                                  title="{{ $file->filename }}">{{ $file->filename }}</span>
                        </div>

                        <div class="flex items-center justify-start md:justify-end gap-2 md:w-4/12 lg:w-4/12 xl:w-3/12">
                            @if($isImage)
                                <button
                                    data-image="{{ asset('storage/'.$file->path) }}"
                                    x-on:click.prevent="$dispatch('open-modal', 'imageModal')"
                                    class="open-image-modal flex items-center justify-center p-2 bg-amber-600 hover:bg-amber-500 rounded-md transition-colors duration-150"
                                    title="Otvoriť"
                                >
                                    <i class="fa-solid fa-eye"></i>
                                    <span class="ml-2 hidden sm:inline">Otvoriť</span>
                                </button>
                            @endif

                            <a href="{{ route('files.download', ['file' => $file->id]) }}"
                               class="flex items-center justify-center p-2 bg-green-600 hover:bg-green-500 rounded-md transition-colors duration-150"
                               title="Stiahnuť">
                                <i class="fa-solid fa-download"></i>
                                <span class="ml-2 hidden sm:inline">Stiahnuť</span>
                            </a>

                            @if(auth()->user()->hasRole(config('constants.roles.admin')))
                                <a href="{{ route('files.show', ['file' => $file->id]) }}"
                                   class="flex items-center justify-center p-2 {{ $file->is_shown ? "bg-yellow-500 hover:bg-yellow-400" : "bg-sky-600 hover:bg-sky-500" }} rounded-md transition-colors duration-150"
                                   title="{{ $file->is_shown ? 'Skryť' : 'Zobraziť' }}">
                                    <i class="fa-solid fa-eye{{ $file->is_shown ? '-slash' : '' }}"></i>
                                    <span
                                        class="ml-2 hidden sm:inline">{{ $file->is_shown ? 'Skryť' : 'Zverejniť' }}</span>
                                </a>
                                <a href="#" data-file-id="{{$file->id}}"
                                   class="delete-file flex items-center justify-center p-2 bg-red-700 hover:bg-red-600 rounded-md transition-colors duration-150"
                                   title="Odstrániť">
                                    <i class="fa-solid fa-trash"></i>
                                    <span class="ml-2 hidden sm:inline">Zmazať</span>
                                </a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-10 bg-gray-700 rounded-lg">
                <i class="fa-solid fa-folder-open text-5xl text-gray-500 mb-4"></i>
                <h3 class="text-xl font-medium text-gray-400">Žiadne súbory neboli pridané.</h3>
                @if(auth()->user()->hasRole(config('constants.roles.admin')))
                    <p class="text-gray-500 mt-2">Súbory môžete pridať pomocou formulára vyššie.</p>
                @endif
            </div>
        @endif
    </div>
</div>

<x-modal
    name="imageModal"
    maxWidth="6xl"
    z="99"
    focusable
>
    <div class="flex justify-between rounded-t-lg dark:border-gray-600">
        <button
            type="button"
            class="text-gray-400 bg-transparent rounded-lg text-sm p-3 ml-auto inline-flex items-center hover:bg-gray-600 hover:text-white"
            x-on:click="$dispatch('close')"
        >
            <i class="fa fa-x"></i>
            <span class="sr-only">Close modal</span>
        </button>
    </div>
    <div class="flex justify-center p-10">
        <img id="modalImage" src="" alt="Preview" class="max-w-full rounded-lg"/>
    </div>
</x-modal>

<script>
    document.querySelectorAll('.open-image-modal').forEach(button => {
        button.addEventListener('click', () => {
            const imageSrc = button.getAttribute('data-image');
            document.getElementById('modalImage').src = imageSrc;
            console.log(imageSrc);
        });
    });
</script>

<link href="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone.css" rel="stylesheet" type="text/css" />
<script src="https://unpkg.com/dropzone@6.0.0-beta.1/dist/dropzone-min.js"></script>
<script>
    const fileUpload = "{{ route('files.store') }}";
</script>
