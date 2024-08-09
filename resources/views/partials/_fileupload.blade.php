@if(auth()->user()->hasRole(config('constants.roles.admin')))
    <div class="w-full border-blue-600 my-5 text-white font-bold tracking-wider">

        <form action="{{ route('files.store') }}" method="post" class="dropzone bg-gray-600 rounded-xl" id="my-dropzone">
            @csrf
            <input type="hidden" name="id_week" value="{{ $week->id }}">
            <div class="dz-message text-center">
                <span class="block m-4">Pusti súbory tu, alebo klikni pre vloženie.</span>
            </div>
        </form>
    </div>
@endif
<div class="border-blue-600 my-3 text-white font-medium tracking-wide">
    @if(isset($files) && count($files) != 0)
        @foreach($files as $file)
            <div class="border-b py-3">
                <span class="content-center"><i class="fa-solid fa-file"></i> {{ $file->filename }}</span>
                <span class="text-center px-10">{{ $file->created_at }}</span>
                @if(auth()->user()->hasRole(config('constants.roles.admin')))
                    <a data-file-id="{{$file->id}}" class="delete-file flex float-end p-2 bg-red-800"><i class="fa-solid fa-trash"></i></a>
                    <a href="{{ route('files.show', ['file' => $file->id]) }}" class="{{ $file->is_shown ? "bg-yellow-600" : "bg-blue-800" }} flex mr-2 float-end p-2"><i class="fa-solid fa-eye"></i></a>
                @endif
                <a href="{{ route('files.download', ['file' => $file->id]) }}" class="flex mx-2 float-end p-2 bg-blue-800"><i class="fa-solid fa-download"></i></a>
            </div>
        @endforeach
	@else
		<h3 class="color-red-800">Žiadne súbory</h3>
    @endif
</div>
