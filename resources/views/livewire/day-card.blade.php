<div @class([
    'relative flex flex-col overflow-hidden rounded-lg border bg-neutral-900 shadow-sm transition',
    'border-indigo-500/80 ring-1 ring-indigo-500/30' => $this->isToday,
    'border-neutral-800 hover:border-neutral-700' => ! $this->isToday,
    'opacity-80' => $locked,
])>
    {{-- Loading overlay with solid badge --}}
    <div wire:loading class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-neutral-950/70 pointer-events-none transition-all duration-200">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg bg-neutral-900 border border-neutral-700 shadow-2xl text-xs font-semibold text-neutral-200">
            <i class="fa-solid fa-circle-notch fa-spin text-sky-400 text-sm"></i>
            <span>Ukladám...</span>
        </div>
    </div>
    <div wire:loading class="absolute top-0 inset-x-0 h-0.5 bg-neutral-800 overflow-hidden z-30 pointer-events-none">
        <div class="h-full bg-indigo-500 animate-pulse w-full"></div>
    </div>

    {{-- Day Card Content (blurred during Livewire loading) --}}
    <div wire:loading.class="blur-sm pointer-events-none select-none" class="flex flex-col flex-1 transition-[filter] duration-200">
        {{-- Full Width Top Action Button (Solid, not transparent) --}}
        <div>
            @if($locked)
                <div class="flex min-h-11 w-full items-center justify-center gap-2 bg-neutral-950 text-xs font-semibold uppercase tracking-wider text-neutral-500 border-b border-neutral-800">
                    <i class="fa-solid fa-lock text-xs"></i>
                    Zamknutý
                </div>
            @elseif($this->mine)
                {{-- Signed Up -> Solid Green Button, on hover turns Rose with Odpísať sa --}}
                <button
                    type="button"
                    wire:click="withdraw"
                    wire:loading.attr="disabled"
                    wire:target="withdraw"
                    class="group/btn flex min-h-11 w-full items-center justify-center gap-2 bg-emerald-600 hover:bg-rose-600 border-b-2 border-emerald-700 hover:border-rose-700 px-3 py-2 text-xs font-bold uppercase tracking-wider text-white transition focus:outline-none disabled:opacity-50 cursor-pointer shadow-sm"
                    title="Kliknutím sa odpíšete z tohto dňa"
                >
                    <span wire:loading.remove wire:target="withdraw" class="flex items-center justify-center gap-1.5 w-full">
                        <span class="inline-flex items-center gap-1.5 group-hover/btn:hidden">
                            <i class="fa-solid fa-check text-xs"></i> Zapísaný
                        </span>
                        <span class="hidden group-hover/btn:inline-flex items-center gap-1.5 text-white">
                            <i class="fa-solid fa-xmark text-xs"></i> Odpísať sa
                        </span>
                    </span>
                    <span wire:loading wire:target="withdraw"><i class="fa-solid fa-circle-notch fa-spin text-xs"></i></span>
                </button>
            @else
                {{-- Not Signed Up -> Solid Neutral Button, turns Indigo on hover --}}
                <button
                    type="button"
                    wire:click="signUp"
                    wire:loading.attr="disabled"
                    wire:target="signUp"
                    class="flex min-h-11 w-full items-center justify-center gap-2 bg-neutral-800 hover:bg-indigo-600 border-b-2 border-neutral-700/80 hover:border-indigo-700 px-3 py-2 text-xs font-bold uppercase tracking-wider text-neutral-200 hover:text-white transition focus:outline-none disabled:opacity-50 cursor-pointer shadow-sm"
                    title="Kliknutím sa zapíšete na tento deň"
                >
                    <span wire:loading.remove wire:target="signUp" class="flex items-center justify-center gap-1.5 w-full">
                        <i class="fa-solid fa-plus text-xs"></i> Zapísať sa
                    </span>
                    <span wire:loading wire:target="signUp"><i class="fa-solid fa-circle-notch fa-spin text-xs"></i></span>
                </button>
            @endif
        </div>

        {{-- Day Info Header --}}
        <div class="flex flex-col items-center justify-center px-4 py-2.5 border-b border-neutral-800 bg-neutral-950 text-center relative">
            @if($this->isToday)
                <span class="inline-flex items-center px-2 py-0.2 rounded text-[10px] font-bold uppercase tracking-wider bg-neutral-800 text-indigo-400 border border-neutral-700 mb-1">
                    Dnes
                </span>
            @endif
            <p class="truncate text-base font-bold text-neutral-100 max-w-full" title="{{ Str::title($this->dayCarbon->locale('sk')->dayName) }}">
                {{ Str::title($this->dayCarbon->locale('sk')->dayName) }}
            </p>
            <p class="mt-0.5 text-xs font-semibold text-neutral-400 tracking-wide">{{ $this->dayCarbon->format('d.m.Y') }}</p>
        </div>

        {{-- Signed-up Entries Container --}}
        <div class="flex flex-col px-3 py-1 flex-1">
            @forelse($this->assignments as $assignment)
                <div wire:key="assignment-{{ $assignment->id }}"
                     title="{{ $assignment->user->lastname }} {{ $assignment->user->name }}@if($assignment->note) ({{ $assignment->note }})@endif"
                     @class([
                        'rows group flex items-center justify-between gap-2 border-b border-neutral-800/60 py-1.5 text-sm leading-snug last:border-b-0 cursor-default',
                        'font-semibold text-white' => $assignment->user_id === auth()->id(),
                        'text-neutral-500 line-through' => ! $assignment->user->is_active,
                        'text-neutral-200' => $assignment->user->is_active,
                    ])>
                    <div class="flex min-w-0 items-center gap-1.5 text-left">
                        <span class="shrink-0 font-semibold text-sm {{ $assignment->user_id === auth()->id() ? 'text-indigo-300' : 'text-neutral-200' }}">
                            @if($this->canViewUsers)
                                <a href="{{ route('profile.show', $assignment->user) }}"
                                   class="transition hover:text-white">{{ $assignment->user }}</a>
                            @else
                                <span>{{ $assignment->user }}</span>
                            @endif
                        </span>

                        @if($assignment->user_id === auth()->id())
                            <span class="px-1 py-0.2 rounded text-[9px] font-bold uppercase tracking-wider bg-neutral-800 text-neutral-300 border border-neutral-700/80">Ja</span>
                        @endif

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
                <p class="rows py-3 text-center text-xs italic text-neutral-500 font-medium">
                    Nikto nie je zapísaný.
                </p>
            @endforelse
        </div>

        {{-- Count Footer --}}
        <div class="border-t border-neutral-800 px-3 py-1.5 text-center bg-neutral-950 mt-auto">
            <span @class([
                'text-xs font-semibold',
                'text-neutral-500' => $this->assignments->count() === 0,
                'text-neutral-400' => $this->assignments->count() > 0,
            ])>
                <i class="fa-solid fa-user-group text-[0.65rem] mr-1 opacity-70"></i>
                {{ $this->assignments->count() }} {{ $this->countLabel }}
            </span>
        </div>
    </div>
</div>
