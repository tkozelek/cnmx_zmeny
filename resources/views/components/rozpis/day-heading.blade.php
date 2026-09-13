{{--
    The day name and date atop one column of the week.

    Shared by the builder and the published plan on purpose: a manager assembling Friday and an
    employee reading it back have to be looking at the same heading, and these two drifted apart
    once already. Whatever each view wants underneath - the filled count, who is in charge - goes
    in the slot.
--}}
@props(['name', 'date'])

@php
    $isToday = $date instanceof \DateTimeInterface ? $date->isToday() : false;
@endphp

<div class="flex flex-col items-center justify-center border-b border-neutral-800 bg-neutral-900/60 px-4 py-3 text-center">
    <div class="flex items-center justify-center gap-1.5 max-w-full">
        <p class="truncate text-lg font-bold text-neutral-100">{{ $name }}</p>
        @if($isToday)
            <span class="rounded-full border border-white/30 bg-white/10 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-white shrink-0">
                Dnes
            </span>
        @endif
    </div>
    <p class="mt-0.5 text-sm font-bold tracking-wide text-neutral-200">{{ $date->format('d.m.Y') }}</p>

    {{ $slot }}
</div>
