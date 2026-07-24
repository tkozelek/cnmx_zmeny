@props(['day', 'user'])

@php
    $isBlocked = $user->hasRole(config('constants.roles.zablokovany'));
    $canManage = auth()->user()->hasRole(config('constants.roles.admin')) && ! $isBlocked;
    $popis = $user->pivot->popis ?? null;
    $dayLabel = $day->date->format('d.m.Y');
@endphp

<div @class([
    'rows group flex items-center gap-1.5 border-b border-slate-700/60 bg-slate-900 px-2 py-1.5 text-sm text-slate-100',
    'justify-between transition-colors hover:bg-slate-800' => $canManage,
    'justify-center' => ! $canManage,
    'text-slate-500 line-through' => $isBlocked,
])>
    <span class="min-w-0 truncate">
        @if($canManage)
            <a href="{{ route('profile.show', $user->id) }}"
               class="transition-colors hover:text-indigo-300"
               title="Zobraziť profil {{ $user }}">{{ $user }}</a>
        @else
            {{ $user }}
        @endif

        @if($popis)
            <span class="ml-1 text-xs italic text-slate-400">({{ $popis }})</span>
        @endif
    </span>

    @if($canManage)
        <form action="{{ route('admin.calendar.userdestroy', ['day' => $day->id, 'user' => $user->id]) }}"
              method="POST"
              class="shrink-0"
              onsubmit='return confirm("Určite chcete zmazať používateľa {{ $user }} zo dňa {{ $dayLabel }}?")'>
            @csrf
            <button type="submit"
                    class="text-red-500 opacity-0 transition-opacity hover:text-red-400 focus:opacity-100 group-hover:opacity-100"
                    title="Odstrániť {{ $user }} z dňa {{ $dayLabel }}">
                <i class="fa-solid fa-times fa-fw"></i>
            </button>
        </form>
    @endif
</div>
