@props(['week'])

<div class="flex items-center justify-center my-2 mb-4 ">
    @if(isset($week->prev_week_id))
        <a class="bg-blue-500 hover:bg-blue-700 text-white font-bold p-4 px-5 rounded" href="{{ url('week/' . ($week->prev_week_id)) }}">
            <i class="fa-solid fa-angle-left"></i>
        </a>
    @endif
    <div class="py-2 mx-10 font-bold text-2xl text-white tracking-wider">{{$week->date_from->format('d.m.Y').' - '.$week->date_to->format('d.m.Y')}}</div>
    @if(isset($week->next_week_id))
        <a class="bg-blue-500 hover:bg-blue-700 text-white font-bold p-4 px-5 rounded" href="{{ url('week/' . ($week->next_week_id)) }}">
            <i class="fa-solid fa-angle-right"></i>
        </a>
    @endif
</div>
