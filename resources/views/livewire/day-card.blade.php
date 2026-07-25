<div @class([
    'flex flex-col overflow-hidden rounded-md border bg-neutral-900 shadow-sm transition',
    'border-neutral-400 ring-1 ring-neutral-400/40' => $this->isToday,
    'border-neutral-800 hover:border-neutral-700' => ! $this->isToday,
    'opacity-75' => $locked,
])>
    {{-- Full Width Top Action Button --}}
    <div>
        @if($locked)
            <div class="flex min-h-11 w-full items-center justify-center gap-2 bg-neutral-800/80 text-xs font-semibold uppercase tracking-wider text-neutral-400 border-b border-neutral-800">
                <i class="fa-solid fa-lock text-xs"></i>
                Zamknutý
            </div>
        @elseif($this->mine)
            {{-- Signed Up -> Green Button with Green Bottom Border --}}
            <button
                type="button"
                wire:click="withdraw"
                wire:loading.attr="disabled"
                wire:target="withdraw"
                class="flex min-h-11 w-full items-center justify-center gap-2 bg-emerald-500/15 border-b border-emerald-500/50 px-3 py-2 text-xs font-bold uppercase tracking-wider text-emerald-300 transition hover:bg-emerald-500/30 hover:text-white focus:outline-none disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="withdraw" class="flex items-center justify-center gap-1.5 w-full">
                    <i class="fa-solid fa-check text-xs"></i> Odpísať sa
                </span>
                <span wire:loading wire:target="withdraw"><i class="fa-solid fa-spinner fa-spin"></i></span>
            </button>
        @else
            {{-- Not Signed Up -> Subtle Red Button with Red Bottom Border --}}
            <button
                type="button"
                wire:click="signUp"
                wire:loading.attr="disabled"
                wire:target="signUp"
                class="flex min-h-11 w-full items-center justify-center gap-2 bg-rose-500/15 border-b border-rose-500/40 px-3 py-2 text-xs font-bold uppercase tracking-wider text-rose-300 transition hover:bg-rose-500/30 hover:text-white focus:outline-none disabled:opacity-50"
            >
                <span wire:loading.remove wire:target="signUp" class="flex items-center justify-center gap-1.5 w-full">
                    <i class="fa-solid fa-plus text-xs"></i> Zapísať sa
                </span>
                <span wire:loading wire:target="signUp"><i class="fa-solid fa-spinner fa-spin"></i></span>
            </button>
        @endif
    </div>

    {{-- Day Info Header --}}
    <div class="flex flex-col items-center justify-center px-4 py-3 border-b border-neutral-800 bg-neutral-900/60 text-center">
        <p class="truncate text-lg font-bold text-neutral-100 max-w-full" title="{{ Str::title($this->dayCarbon->locale('sk')->dayName) }}">
            {{ Str::title($this->dayCarbon->locale('sk')->dayName) }}
        </p>
        <p class="mt-0.5 text-sm font-bold text-neutral-200 tracking-wide">{{ $this->dayCarbon->format('d.m.Y') }}</p>
    </div>

    {{-- Signed-up Entries Container --}}
    <div class="flex flex-col px-3 py-0.5">
        @forelse($this->assignments as $assignment)
            <div wire:key="assignment-{{ $assignment->id }}" @class([
                'rows group flex items-center justify-between gap-2 border-b border-neutral-800/60 py-1 text-sm leading-snug last:border-b-0',
                'font-semibold text-white' => $assignment->user_id === auth()->id(),
                'text-neutral-500 line-through' => ! $assignment->user->is_active,
                'text-neutral-200' => $assignment->user->is_active,
            ])>
                <div class="flex min-w-0 items-center gap-1.5 text-left truncate">
                    <span class="min-w-0 truncate font-semibold text-sm text-neutral-200" title="{{ $assignment->user->name }} {{ $assignment->user->lastname }}">
                        @if($this->canViewUsers)
                            <a href="{{ route('profile.show', $assignment->user) }}"
                               class="truncate transition hover:text-white"
                               title="{{ $assignment->user->name }} {{ $assignment->user->lastname }}">{{ $assignment->user->name }} {{ $assignment->user->lastname }}</a>
                        @else
                            <span class="truncate" title="{{ $assignment->user->name }} {{ $assignment->user->lastname }}">{{ $assignment->user->name }} {{ $assignment->user->lastname }}</span>
                        @endif
                    </span>

                    @if($assignment->note)
                        <span class="truncate text-xs font-normal text-neutral-400 shrink-0" title="{{ $assignment->note }}">
                            ({{ $assignment->note }})
                        </span>
                    @endif
                </div>

                {{-- Hidden Position Badge --}}
                <div class="hidden">
                    @if($assignment->position)
                        <span class="truncate rounded px-1.5 py-0.5 text-[0.65rem] font-medium text-neutral-300 border border-neutral-700 bg-neutral-800"
                              title="{{ $assignment->position->label() }}">
                            {{ $assignment->position->label() }}
                        </span>
                    @endif
                </div>

                @if($this->canRemove($assignment))
                    <div class="flex shrink-0 items-center ml-auto">
                        <button
                            type="button"
                            wire:click="remove({{ $assignment->id }})"
                            wire:confirm="Určite odpísať {{ $assignment->user }} zo dňa {{ $this->dayCarbon->format('d.m.Y') }}?"
                            class="flex h-5 w-5 p-0.5 items-center justify-center rounded text-neutral-400 transition hover:bg-rose-500/20 hover:text-rose-300 focus:opacity-100 focus:outline-none sm:opacity-0 sm:group-hover:opacity-100"
                            title="Odpísať {{ $assignment->user }}"
                        >
                            <i class="fa-solid fa-xmark text-[0.7rem]"></i>
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <p class="rows py-2 text-center text-xs italic text-neutral-500 font-medium">
                Nikto nie je zapísaný.
            </p>
        @endforelse
    </div>

    {{-- Count Footer --}}
    <div class="border-t border-neutral-800/80 px-3 py-1.5 text-center bg-neutral-900/80">
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
