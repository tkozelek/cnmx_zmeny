@props(['day', 'locked'])

<div id="c-{{ $day->id }}" class="border-gray-600">

    @if($locked)
        <x-day-button :id="$day->id" class="bg-gray-300" disabled>ZAMKNUTÝ</x-day-button>
    @elseif(isset($day->users) && App\Helpers::findIdInArray(auth()->user()->id, $day->users))
        <x-day-button :id="$day->id" selected="1" class="bg-green-300 hover:bg-green-600">ODPISAŤ</x-day-button>
    @else
        <x-day-button :id="$day->id" selected="0" class="bg-red-300 hover:bg-red-400">ZAPISAŤ</x-day-button>
    @endif

    <div class="bg-gray-800 text-white py-1 rounded-t-sm shadow-md tracking-wide">
        <p class="font-semibold text-xl">
            {{ mb_convert_case($day->date->locale('sk')->dayName, MB_CASE_TITLE, 'UTF-8') }}
        </p>
        <p class="text-lg/50 text-slate-300">{{ $day->date->format('d.m.Y') }}</p>
        <p class="text-xs text-indigo-400 mt-1">Zapísaných: ({{ count($day->users) }})</p>
    </div>
        <div id="users" class="users-container">
            @isset($day->users)
                @foreach($day->users as $user)
                    @php
                        $isBlocked = $user->hasRole(config('constants.roles.zablokovany'));
                        $isAdminView = auth()->user()->hasRole(config('constants.roles.admin'));
                        $popis = $user->pivot->popis ?? null;
                    @endphp

                    <div @class([
                        'bg-gray-900 text-white border-b border-gray-700 py-1 shadow-md text-md rows px-1 flex items-center',
                        'line-through' => $isBlocked,
                        'justify-between group' => $isAdminView && !$isBlocked,
                        'justify-center' => !$isAdminView || $isBlocked,
                    ])>
                        <span class="truncate max-w-[calc(100%-20px)] ">
                            @if($isAdminView && !$isBlocked)
                                <a href="{{ route('profile.show', $user->id) }}" class="hover:text-indigo-300 transition-colors duration-150" title="Zobraziť profil {{ $user }}">
                                    {{ $user }}
                                </a>
                            @else
                                {{ $user }}
                            @endif
                            @if($popis)
                                <span class="text-slate-400/80 italic text-sm ml-1">({{ $popis }})</span>
                            @endif
                        </span>

                        @if($isAdminView && !$isBlocked)
                            <form onsubmit='return confirm("Určite chcete zmazať používateľa {{$user}} zo dňa {{$day->date->format('d.m.Y')}}?")' action="{{ route('admin.calendar.userdestroy', ['day' => $day->id, 'user' => $user->id]) }}" method="POST" class="ml-1 shrink-0">
                                @csrf
                                <button type="submit" class="text-red-500 hover:text-red-400 opacity-0 group-hover:opacity-100 transition-opacity duration-150 focus:opacity-100" title="Odstrániť {{ $user }} z dňa {{$day->date->format('d.m.Y')}}">
                                    <i class="fa-solid fa-times fa-fw"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            @endisset
        </div>
</div>


