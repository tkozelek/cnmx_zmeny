<div @class([
    'relative flex flex-col overflow-hidden rounded-lg border bg-neutral-900 shadow-sm transition',
    'border-emerald-500/70 ring-1 ring-emerald-500/30' => $this->mine && ! $this->isToday,
    'border-indigo-500 ring-2 ring-indigo-500/40' => $this->isToday,
    'border-amber-500/70' => $this->isHoliday && ! $this->mine && ! $this->isToday,
    'border-neutral-800 hover:border-neutral-700' => ! $this->mine && ! $this->isToday && ! $this->isHoliday,
    'opacity-75' => $locked && ! $this->mine,
])>
    {{-- Loading overlay with solid badge --}}
    <div wire:loading class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-neutral-950/70 pointer-events-none transition-all duration-200">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-neutral-900 border border-neutral-700 shadow-2xl text-xs font-semibold text-neutral-200">
            <i class="fa-solid fa-circle-notch fa-spin text-brand-400 text-sm"></i>
            <span>Ukladám...</span>
        </div>
    </div>
    <div wire:loading class="absolute top-0 inset-x-0 h-0.5 bg-neutral-800 overflow-hidden z-30 pointer-events-none">
        <div class="h-full bg-indigo-500 animate-pulse w-full"></div>
    </div>

    {{-- Day Card Content (blurred during Livewire loading) --}}
    <div wire:loading.class="blur-sm pointer-events-none select-none" class="flex flex-col flex-1 transition-[filter] duration-200">
        {{-- Full Width Top Action Button (Solid & Vibrant) --}}
        <div>
            @if($locked)
                @if($this->mine)
                    {{-- Locked but signed up: clear green status indicator --}}
                    <div class="flex min-h-11 w-full items-center justify-center gap-2 bg-emerald-700 text-xs font-bold uppercase tracking-wider text-white border-b-2 border-emerald-800 shadow-sm">
                        <span>Zapísaný</span>
                        <span class="text-emerald-200 text-[10px] font-semibold tracking-wide">(Zamknuté)</span>
                    </div>
                @else
                    {{-- Locked and not signed up: clear neutral lock status --}}
                    <div class="flex min-h-11 w-full items-center justify-center gap-2 bg-neutral-950 text-xs font-semibold uppercase tracking-wider text-neutral-400 border-b border-neutral-800">
                        <span>Zamknutý</span>
                    </div>
                @endif
            @elseif($this->mine)
                {{-- Signed Up -> Solid Vibrant Emerald Green Button, on hover turns Rose with Odpísať sa --}}
                <button
                    type="button"
                    wire:click="withdraw"
                    wire:loading.attr="disabled"
                    wire:target="withdraw"
                    class="group/btn flex min-h-11 w-full items-center justify-center gap-2 bg-emerald-600 hover:bg-rose-600 border-b-2 border-emerald-700 hover:border-rose-700 px-3 py-2 text-xs font-bold uppercase tracking-wider text-white transition focus:outline-none disabled:opacity-50 cursor-pointer shadow-sm"
                    title="Kliknutím sa odpíšete z tohto dňa"
                >
                    <span wire:loading.remove wire:target="withdraw" class="flex items-center justify-center gap-1.5 w-full">
                        <span class="group-hover/btn:hidden">Zapísaný</span>
                        <span class="hidden group-hover/btn:inline text-white">Odpísať sa</span>
                    </span>
                    <span wire:loading wire:target="withdraw"><i class="fa-solid fa-circle-notch fa-spin text-xs"></i></span>
                </button>
            @else
                {{-- Not Signed Up -> Solid Vibrant Sky Blue Button --}}
                <button
                    type="button"
                    wire:click="signUp"
                    wire:loading.attr="disabled"
                    wire:target="signUp"
                    class="flex min-h-11 w-full items-center justify-center gap-2 bg-brand-600 hover:bg-brand-500 border-b-2 border-brand-700 hover:border-brand-600 px-3 py-2 text-xs font-bold uppercase tracking-wider text-white transition focus:outline-none disabled:opacity-50 cursor-pointer shadow-sm"
                    title="Kliknutím sa zapíšete na tento deň"
                >
                    <span wire:loading.remove wire:target="signUp" class="flex items-center justify-center gap-1.5 w-full">
                        Zapísať sa
                    </span>
                    <span wire:loading wire:target="signUp"><i class="fa-solid fa-circle-notch fa-spin text-xs"></i></span>
                </button>
            @endif
        </div>

        {{-- Day Info Header --}}
        <div @class([
            'flex flex-col items-center justify-center px-4 py-2.5 border-b text-center relative transition-colors',
            'bg-emerald-950/40 border-emerald-800/60' => $this->mine && ! $this->isToday,
            'bg-neutral-950 border-neutral-500' => $this->isToday,
            'bg-neutral-950 border-neutral-800' => ! $this->mine && ! $this->isToday,
        ])>
            <p @class([
                'truncate text-base font-bold max-w-full',
                'text-emerald-200' => $this->mine && ! $this->isToday,
                'text-neutral-100' => ! ($this->mine && ! $this->isToday),
            ]) title="{{ Str::title($this->dayCarbon->locale('sk')->dayName) }}">
                {{ Str::title($this->dayCarbon->locale('sk')->dayName) }}
            </p>
            <p @class([
                'mt-0.5 text-xs font-semibold tracking-wide',
                'text-emerald-300/80' => $this->mine && ! $this->isToday,
                'text-neutral-400' => ! ($this->mine && ! $this->isToday),
            ])>{{ $this->dayCarbon->format('d.m.Y') }}</p>
        </div>

        {{-- Signed-up Entries Container --}}
        <div class="day-card-entries flex flex-col px-3 py-1 flex-1">
            @forelse($this->assignments as $assignment)
                <div wire:key="assignment-{{ $assignment->id }}"
                     title="{{ $assignment->user->lastname }} {{ $assignment->user->name }}@if($assignment->note) ({{ $assignment->note }})@endif"
                     @class([
                        'rows group flex items-center justify-between gap-2 border-b border-neutral-800/60 py-1.5 text-sm leading-snug last:border-b-0 cursor-default',
                        'font-semibold text-white' => $assignment->user_id === auth()->id(),
                        'text-neutral-400 line-through' => ! $assignment->user->is_active,
                        'text-neutral-200' => $assignment->user->is_active,
                    ])>
                    <div class="flex min-w-0 items-center gap-1.5 text-left">
                        <span class="shrink-0 font-semibold text-sm {{ $assignment->user_id === auth()->id() ? 'text-emerald-400 font-bold' : 'text-neutral-200' }}">
                            @if($this->canViewUsers)
                                <a href="{{ route('profile.show', $assignment->user) }}"
                                   class="transition {{ $assignment->user_id === auth()->id() ? 'hover:text-emerald-300' : 'hover:text-white' }}">{{ $assignment->user }}</a>
                            @else
                                <span>{{ $assignment->user }}</span>
                            @endif
                        </span>

                        @if($assignment->note)
                            <span class="min-w-0 truncate text-xs font-normal text-neutral-400">
                                ({{ $assignment->note }})
                            </span>
                        @endif
                    </div>

                    @if($this->canRemove($assignment))
                        <div class="flex shrink-0 items-center ml-auto">
                            <button
                                type="button"
                                wire:click="remove({{ $assignment->id }})"
                                wire:confirm="Určite odpísať {{ $assignment->user }} zo dňa {{ $this->dayCarbon->format('d.m.Y') }}?"
                                wire:loading.attr="disabled"
                                class="flex h-5 w-5 items-center justify-center rounded text-neutral-400 transition hover:bg-rose-600 hover:text-white focus:opacity-100 focus:outline-none sm:opacity-0 sm:group-hover:opacity-100 disabled:opacity-50"
                                title="Odpísať {{ $assignment->user }}"
                                aria-label="Odpísať {{ $assignment->user }}"
                            >
                                <i class="fa-solid fa-xmark text-xs"></i>
                            </button>
                        </div>
                    @endif
                </div>
            @empty
                <p class="rows py-3 text-center text-xs italic text-neutral-400 font-medium">
                    Nikto nie je zapísaný.
                </p>
            @endforelse
        </div>

        {{-- Count Footer --}}
        <div class="day-card-footer border-t border-neutral-800 px-3 py-1.5 flex flex-col items-center gap-1 bg-neutral-950 mt-auto">
            @if($this->isHoliday)
                <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-amber-400 bg-amber-500/15 border border-amber-500/30">
                    Sviatok
                </span>
            @endif
            <span class="text-xs font-semibold text-neutral-400">
                {{ $this->assignments->count() }} {{ $this->countLabel }}
            </span>
        </div>
    </div>
</div>
