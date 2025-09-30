<div class="flex items-center justify-center my-2 mb-4 ">

    @if($week->locked)
        <a class="bg-amber-600 hover:bg-amber-700 text-white font-bold p-2 px-3 rounded-md transition-colors duration-150" href="{{ route('admin.calendar.lock', ['week' => $week->id]) }}">
            <i class="fa-solid fa-unlock"></i>
            <span class="ml-1 font-medium">Odomkni</span>
        </a>
    @else
        <a class="bg-red-600 hover:bg-red-800 text-white font-bold p-2 px-3 rounded-md transition-colors duration-150" href="{{ route('admin.calendar.lock', ['week' => $week->id]) }}">
            <i class="fa-solid fa-unlock"></i>
            <span class="ml-1 font-medium">Zamkni</span>
        </a>
    @endif

    <a href="{{ route('admin.calendar.export', ['week' => $week->id]) }}" class="bg-green-600 hover:bg-green-700 text-white mx-5 font-bold p-2 rounded-md transition-colors duration-150" type="submit">
        <i class="fa-solid fa-download"></i>
        <span class="ml-1 font-medium">Excel</span>
    </a>

    <div class="mt-2 mb-2 float-right">
        @include('partials._fileuploadmodal')
    </div>
</div>
