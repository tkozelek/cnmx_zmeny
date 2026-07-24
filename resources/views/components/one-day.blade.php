@props(['day', 'locked'])

@php
    $signedUp = $day->users;
    $isSignedUp = $signedUp->contains(auth()->id());
    $isToday = $day->date->isToday();
@endphp

<div id="c-{{ $day->id }}" @class([
    'flex flex-col overflow-hidden rounded-lg border bg-slate-800 shadow-lg',
    'border-indigo-500/70 ring-1 ring-indigo-500/40' => $isToday,
    'border-slate-700' => ! $isToday,
])>
    <div class="px-2 py-2 tracking-wide">
        <p class="text-xl font-semibold text-white">
            {{ Str::title($day->date->locale('sk')->dayName) }}
        </p>
        <p class="text-sm text-slate-400">{{ $day->date->format('d.m.Y') }}</p>
        <p class="mt-1 text-xs font-medium text-indigo-400">Zapísaných: {{ $signedUp->count() }}</p>
    </div>

    @if($locked)
        <x-day-button :id="$day->id" class="bg-slate-600 text-slate-300" disabled>ZAMKNUTÝ</x-day-button>
    @elseif($isSignedUp)
        <x-day-button :id="$day->id" selected="1" class="bg-emerald-400 text-slate-900 hover:bg-emerald-500">ODPISAŤ</x-day-button>
    @else
        <x-day-button :id="$day->id" selected="0" class="bg-rose-400 text-slate-900 hover:bg-rose-500">ZAPISAŤ</x-day-button>
    @endif

    <x-day-user-list :day="$day"/>
</div>
