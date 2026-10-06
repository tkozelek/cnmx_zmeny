@php
    $dayName = Str::title($this->dayCarbon->locale('sk')->dayName);
    $shortDate = $this->dayCarbon->format('d.m.');
@endphp
<div @class([
    'relative flex flex-col overflow-hidden rounded-lg border bg-neutral-900 transition',
    'border-emerald-500/70' => $this->mine && ! $this->isToday,
    'border-indigo-500' => $this->isToday,
    'border-neutral-800 hover:border-neutral-700' => ! $this->mine && ! $this->isToday,
])>
    {{-- Day card content, blurred while this card's own request is running --}}
    <div wire:loading.class.delay="blur-sm pointer-events-none select-none" class="flex flex-col flex-1 transition-[filter] duration-200">
        {{-- `armed` lives here, not on the button: this wrapper survives the morph between the two buttons. --}}
        <div x-data="{ armed: false }">
            @if($locked)
                @if($this->mine)
                    <div class="flex min-h-11 w-full items-center justify-center gap-2 bg-emerald-700 text-sm font-semibold text-white">
                        <span>Zapísaný</span>
                        <i class="fa-solid fa-lock text-xs text-emerald-100" aria-hidden="true"></i>
                        <span class="sr-only">– týždeň je zamknutý</span>
                    </div>
                @else
                    <div class="flex min-h-11 w-full items-center justify-center gap-2 border-b border-neutral-800 bg-neutral-950 text-sm font-medium text-neutral-400">
                        <span>Zamknutý</span>
                    </div>
                @endif
            @elseif($this->mine)
                {{-- With a mouse the hover already says "Odpísať sa", so one click withdraws. On touch
                     there is no hover: the first tap turns the button into the question, the second
                     tap within 3 s withdraws. --}}
                <button
                    type="button"
                    @click="if (armed || matchMedia('(hover: hover)').matches) { armed = false; $wire.withdraw() } else { armed = true; setTimeout(() => armed = false, 3000) }"
                    wire:target="withdraw"
                    :class="armed && '!bg-rose-700'"
                    class="group/btn flex min-h-11 w-full cursor-pointer items-center justify-center gap-2 bg-emerald-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 focus-visible:bg-rose-700 focus-visible:outline-offset-[-4px]"
                >
                    <span wire:loading.remove wire:target="withdraw">
                        <span x-show="! armed">
                            <span class="group-hover/btn:hidden group-focus-visible/btn:hidden">Zapísaný</span>
                            <span class="hidden group-hover/btn:inline group-focus-visible/btn:inline">Odpísať sa</span>
                        </span>
                        <span x-show="armed" x-cloak>Ťuknite znova na odpísanie</span>
                        <span class="sr-only">– {{ $dayName }} {{ $shortDate }}</span>
                    </span>
                    <span wire:loading wire:target="withdraw"><i class="fa-solid fa-circle-notch fa-spin text-xs" aria-hidden="true"></i></span>
                </button>
            @else
                <button
                    type="button"
                    wire:click="signUp"
                    wire:target="signUp"
                    class="flex min-h-11 w-full cursor-pointer items-center justify-center gap-2 bg-brand-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-offset-[-4px]"
                >
                    <span wire:loading.remove wire:target="signUp">
                        Zapísať sa<span class="sr-only"> – {{ $dayName }} {{ $shortDate }}</span>
                    </span>
                    <span wire:loading wire:target="signUp"><i class="fa-solid fa-circle-notch fa-spin text-xs" aria-hidden="true"></i></span>
                </button>
            @endif
        </div>

        {{-- Day header --}}
        <div @class([
            'flex flex-col items-center justify-center px-4 py-2.5 border-b text-center',
            'bg-emerald-950/40 border-emerald-800/60' => $this->mine && ! $this->isToday,
            'bg-neutral-950 border-neutral-500' => $this->isToday,
            'bg-neutral-950 border-neutral-800' => ! $this->mine && ! $this->isToday,
        ])>
            <h2 @class([
                'truncate text-base font-bold max-w-full',
                'text-emerald-200' => $this->mine && ! $this->isToday,
                'text-neutral-100' => ! ($this->mine && ! $this->isToday),
            ])>
                {{ $dayName }}@if($this->isToday)<span class="sr-only">, dnes</span>@endif
            </h2>
            <p @class([
                'mt-0.5 text-xs font-semibold',
                'text-emerald-300/80' => $this->mine && ! $this->isToday,
                'text-neutral-400' => ! ($this->mine && ! $this->isToday),
            ])>
                {{ $this->dayCarbon->format('d.m.Y') }}@if($this->isHoliday)<span class="text-rose-300"> · Sviatok</span>@endif
            </p>
        </div>

        {{-- Who signed up --}}
        <div class="day-card-entries flex flex-col px-3 py-1 flex-1">
            @forelse($this->assignments as $assignment)
                <div wire:key="assignment-{{ $assignment->id }}"
                     @class([
                        'rows group flex items-start justify-between gap-2 border-b border-neutral-800/60 py-1.5 text-sm leading-snug last:border-b-0',
                        'font-semibold text-white' => $assignment->user_id === auth()->id(),
                        'text-neutral-400 line-through' => ! $assignment->user->is_active,
                        'text-neutral-200' => $assignment->user->is_active,
                    ])>
                    <div class="flex min-w-0 flex-wrap items-baseline gap-x-1.5 text-left">
                        <span class="font-semibold text-sm {{ $assignment->user_id === auth()->id() ? 'text-emerald-400 font-bold' : 'text-neutral-200' }}">
                            @if($this->canViewUsers)
                                <a href="{{ route('profile.show', $assignment->user) }}"
                                   class="transition {{ $assignment->user_id === auth()->id() ? 'hover:text-emerald-300' : 'hover:text-white' }}">{{ $assignment->user }}</a>
                            @else
                                <span>{{ $assignment->user }}</span>
                            @endif
                        </span>

                        @if($assignment->note)
                            <span class="min-w-0 break-words text-xs font-normal text-neutral-400">({{ $assignment->note }})</span>
                        @endif
                    </div>

                    @if($this->canRemove($assignment))
                        {{-- Revealed on hover only where hovering exists - on touch it is always shown. --}}
                        <button
                            type="button"
                            wire:click="remove({{ $assignment->id }})"
                            wire:confirm="Odpísať {{ $assignment->user }} z {{ $shortDate }}? Nedostane o tom upozornenie."
                            class="-my-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded text-neutral-400 transition hover:bg-rose-700 hover:text-white focus-visible:opacity-100 sm:[@media(hover:hover)]:opacity-0 sm:group-hover:opacity-100"
                            title="Odpísať {{ $assignment->user }}"
                            aria-label="Odpísať {{ $assignment->user }} – {{ $dayName }} {{ $shortDate }}"
                        >
                            <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>
            @empty
                <p class="rows py-3 text-center text-xs italic text-neutral-400 font-medium">
                    Nikto nie je zapísaný.
                </p>
            @endforelse
        </div>

        @if($this->assignments->isNotEmpty())
            <div class="day-card-footer mt-auto border-t border-neutral-800 bg-neutral-950 px-3 py-1.5 text-center">
                <span class="text-xs font-semibold text-neutral-400">
                    {{ $this->assignments->count() }} {{ $this->countLabel }}
                </span>
            </div>
        @endif

        {{-- Manager-only, shown by the "losovanie" toggle (see app.css .show-draw) --}}
        @can('lock', \App\Models\Assignment::class)
            <div class="draw-list border-t border-neutral-800 px-3 py-2">
                <p class="text-xs font-medium text-neutral-300">
                    K dispozícii na losovanie ({{ count($this->drawable) }})
                </p>
                <p class="mt-1 text-xs leading-relaxed text-neutral-400">
                    @forelse($this->drawable as $name)
                        <span class="whitespace-nowrap">{{ $name }}</span>{{ $loop->last ? '' : ',' }}
                    @empty
                        Nikto ďalší nie je voľný.
                    @endforelse
                </p>
            </div>
        @endcan
    </div>
</div>
