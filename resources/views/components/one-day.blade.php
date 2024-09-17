@props(['day', 'locked'])

<div id="c-{{ $day->id }}" class="border-gray-600">

    @if($locked)
        <x-day-button :id="$day->id" class="bg-gray-300" disabled>ZAMKNUTÝ</x-day-button>
    @elseif(isset($day->users) && App\Helpers::findIdInArray(auth()->user()->id, $day->users))
        <x-day-button :id="$day->id" class="bg-green-300 hover:bg-green-600">ODPISAŤ</x-day-button>
    @else
        <x-day-button :id="$day->id" class="bg-red-300 hover:bg-red-400">ZAPISAŤ</x-day-button>
    @endif

    <div class="bg-gray-800 text-white py-2 rounded-t-sm shadow-md tracking-wide">
        {{ mb_convert_case($day->date->locale('sk')->dayName, MB_CASE_TITLE, 'UTF-8') }} <div class="inline text-xs">({{ count($day->users) }})</div> <br> {{ $day->date->format('d.m.Y') }}
    </div>
        <div id="users" class="users-container">
            @isset($day->users)
                @foreach($day->users as $user)
                    @if($user->hasRole(config('constants.roles.zablokovany')))
                        <x-row-user class="line-through">
                            {{ $user.' ' }} @isset($user->pivot->popis) <small>({{$user->pivot->popis}})</small> @endisset
                        </x-row-user>
                    @elseif (auth()->user()->hasRole(config('constants.roles.admin')))
                        <x-row-user class="flex justify-center hover:gap-3 group">
                            <a href="{{ route('profile.show', $user->id) }}">{{ $user.' ' }} @isset($user->pivot->popis) <small>({{$user->pivot->popis}})</small> @endisset </a>
                            <form action="{{ route('admin.calendar.userdestroy', ['day' => $day->id, 'user' => $user->id]) }}" method="POST" class="">
                                @csrf
                                <button type="submit" class="text-black font-bold rounded ml-5 hidden group-hover:block" style="margin: 0;">
                                    <i class="fa-solid fa-x text-red-900"></i>
                                </button>
                            </form>
                        </x-row-user>
                    @else
                        <x-row-user>
                            {{ $user.' ' }} @isset($user->pivot->popis) <small>({{$user->pivot->popis}})</small> @endisset
                        </x-row-user>
                    @endif
                @endforeach
            @endisset
        </div>
</div>


