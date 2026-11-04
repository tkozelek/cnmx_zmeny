<div @class([
    'day-card relative flex flex-col overflow-hidden rounded-lg border bg-neutral-900 transition',
    'day-card--mine' => $this->mine,
    'day-card--today' => $this->isToday,
    'border-emerald-500/70' => $this->mine && ! $this->isToday,
    'border-brand-500' => $this->isToday,
    'border-neutral-800 hover:border-neutral-700' => ! $this->mine && ! $this->isToday,
])>
    {{-- Day card content, blurred while this card's own request is running --}}
    <div wire:loading.class.delay="blur-sm pointer-events-none select-none" class="day-card__body flex flex-col flex-1 transition-[filter] duration-200">
        {{-- `armed` lives here, not on the button: this wrapper survives the morph between the two buttons.
             `refocus` hands keyboard focus from the clicked button to the one that replaces it. --}}
        <div x-data="{ armed: false, disarm: null, refocus(call) { const had = this.$root.contains(document.activeElement); call.then(() => had && requestAnimationFrame(() => this.$root.querySelector('button')?.focus())) } }" class="day-card__action">
            @if($locked || $this->isPastForMe)
                @if($this->mine)
                    <div class="flex min-h-11 w-full items-center justify-center gap-2 bg-emerald-700 text-sm font-semibold text-white">
                        <span>Zapísaný</span>
                        <i class="fa-solid fa-lock text-xs text-emerald-100" aria-hidden="true"></i>
                        <span class="sr-only">– {{ $locked ? 'týždeň je zamknutý' : 'deň už uplynul' }}</span>
                    </div>
                @else
                    <div class="flex min-h-11 w-full items-center justify-center gap-2 border-b border-neutral-800 bg-neutral-950 text-sm font-medium text-neutral-400">
                        <span>{{ $locked ? 'Zamknutý' : 'Uplynulý deň' }}</span>
                    </div>
                @endif
            @elseif(! $this->isMember)
                <div class="flex min-h-11 w-full items-center justify-center gap-2 border-b border-neutral-800 bg-neutral-950 text-sm font-medium text-neutral-400">
                    <span>Nie si členom kina</span>
                </div>
            @elseif($absent && ! $this->mine)
                {{-- Amber, not the grey of "Zamknutý": the day is open, just not for you. --}}
                <div class="flex min-h-11 w-full items-center justify-center gap-2 border-b border-amber-500/20 bg-amber-500/10 text-sm font-medium text-amber-300">
                    <i class="fa-solid fa-umbrella-beach text-xs" aria-hidden="true"></i>
                    <span>Absencia</span>
                    <span class="sr-only">– {{ $this->dayName }} {{ $this->shortDate }}, na tento deň máš nahlásenú absenciu</span>
                </div>
            @elseif($this->mine)
                {{-- With a mouse the hover already says "Odpísať sa", so one click withdraws. On touch
                     there is no hover: the first tap turns the button into the question, the second
                     tap within 6 s withdraws. Leaving the button disarms it as well. --}}
                {{-- Distinct keys: morphing one button into the other kept stale wrappers and hid the label. --}}
                <button
                    wire:key="withdraw"
                    type="button"
                    @click="if (armed || matchMedia('(hover: hover)').matches) { armed = false; refocus($wire.withdraw()) } else { armed = true; clearTimeout(disarm); disarm = setTimeout(() => armed = false, 6000) }"
                    @blur="armed = false"
                    wire:loading.attr="disabled"
                    wire:target="withdraw"
                    :class="armed && '!bg-rose-700'"
                    {{-- Starts with the visible text (voice control), then says what the button does. --}}
                    :aria-label="armed ? 'Ťukni znova na odpísanie – {{ $this->dayName }} {{ $this->shortDate }}' : 'Zapísaný – {{ $this->dayName }} {{ $this->shortDate }}, odpísať sa'"
                    class="group/btn flex min-h-11 w-full cursor-pointer items-center justify-center gap-2 bg-emerald-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 focus-visible:bg-rose-700 focus-visible:outline-offset-[-4px] focus-visible:!outline-white"
                >
                    <span wire:loading.remove wire:target="withdraw">
                        <span x-show="! armed">
                            <span class="group-hover/btn:hidden group-focus-visible/btn:hidden">Zapísaný</span>
                            <span class="hidden group-hover/btn:inline group-focus-visible/btn:inline">Odpísať sa</span>
                        </span>
                        <span x-show="armed" x-cloak>Ťukni znova na odpísanie</span>
                        <span class="sr-only">– {{ $this->dayName }} {{ $this->shortDate }}</span>
                    </span>
                    <span wire:loading wire:target="withdraw"><i class="fa-solid fa-circle-notch fa-spin text-xs" aria-hidden="true"></i></span>
                </button>
                {{-- The name change alone is rarely announced on phones. --}}
                <span class="sr-only" aria-live="polite" x-text="armed ? 'Ťukni znova na odpísanie' : ''"></span>
            @else
                <button
                    wire:key="sign-up"
                    type="button"
                    {{-- The note goes along with the tap: its own debounced save may still be on its way. --}}
                    @click="refocus($wire.signUp(document.getElementById('extra-note')?.value ?? null))"
                    wire:loading.attr="disabled"
                    wire:target="signUp"
                    class="flex min-h-11 w-full cursor-pointer items-center justify-center gap-2 bg-brand-700 px-3 py-2 text-sm font-semibold text-white transition hover:bg-brand-800 focus-visible:outline-offset-[-4px] focus-visible:!outline-white"
                >
                    <span wire:loading.remove wire:target="signUp">
                        Zapísať sa<span class="sr-only"> – {{ $this->dayName }} {{ $this->shortDate }}</span>
                    </span>
                    <span wire:loading wire:target="signUp"><i class="fa-solid fa-circle-notch fa-spin text-xs" aria-hidden="true"></i></span>
                </button>
            @endif
        </div>

        {{-- Day header --}}
        <div @class([
            'day-card__head flex flex-col items-center justify-center px-4 py-2.5 border-b text-center',
            'bg-emerald-950/40 border-emerald-800/60' => $this->mine && ! $this->isToday,
            'bg-neutral-950 border-neutral-500' => $this->isToday,
            'bg-neutral-950 border-neutral-800' => ! $this->mine && ! $this->isToday,
        ])>
            <h2 @class([
                'truncate text-base font-bold max-w-full',
                'text-emerald-200' => $this->mine && ! $this->isToday,
                'text-neutral-100' => ! ($this->mine && ! $this->isToday),
            ])>
                {{ $this->dayName }}@if($this->isToday)<span class="sr-only">, dnes</span>@endif
            </h2>
            <p @class([
                'mt-0.5 text-xs font-semibold',
                'text-emerald-300/80' => $this->mine && ! $this->isToday,
                'text-neutral-400' => ! ($this->mine && ! $this->isToday),
            ])>
                {{ $this->fullDate }}@if($this->isHoliday)<span class="text-brand-300"> · Sviatok</span>@endif
            </p>
        </div>

        {{-- Who signed up --}}
        <div class="day-card-entries flex flex-col px-3 py-1 flex-1">
            @forelse($this->assignments as $assignment)
                <div wire:key="assignment-{{ $assignment->id }}"
                     @class([
                        'rows group flex items-start justify-between gap-2 border-b border-neutral-800/60 py-1.5 text-sm leading-snug last:border-b-0',
                        'font-semibold text-white' => $this->isOwn($assignment),
                        {{-- Whether a colleague's account is blocked is the managers' business only. --}}
                        'text-neutral-400 line-through' => ! $assignment->user->is_active && $this->canViewUsers,
                        'text-neutral-200' => $assignment->user->is_active || ! $this->canViewUsers,
                    ])>
                    <div class="flex min-w-0 flex-wrap items-baseline gap-x-1.5 text-left">
                        <span class="font-semibold text-sm {{ $this->isOwn($assignment) ? 'text-emerald-400 font-bold' : 'text-neutral-200' }}">
                            @if($this->canViewUsers)
                                <a href="{{ route('profile.show', $assignment->user) }}"
                                   class="transition {{ $this->isOwn($assignment) ? 'hover:text-emerald-300' : 'hover:text-white' }}">{{ $assignment->user }}</a>
                            @else
                                <span>{{ $assignment->user }}</span>
                            @endif
                        </span>

                        @if($assignment->note)
                            <span class="min-w-0 break-words text-xs font-normal text-neutral-400">({{ $assignment->note }})</span>
                        @endif
                    </div>

                    @if($this->canRemove($assignment))
                        {{-- Revealed on hover only where hovering exists - on touch it is always shown, with a
                             finger-sized hit area that the negative margins keep from stretching the row. --}}
                        <button
                            type="button"
                            wire:click="remove({{ $assignment->id }})"
                            wire:confirm="Odstrániť zápis: {{ $assignment->user }} ({{ $this->shortDate }})? Nedostane o tom upozornenie."
                            class="-my-1 flex h-7 w-7 shrink-0 items-center justify-center rounded text-neutral-400 transition hover:bg-rose-700 hover:text-white [@media(hover:none)]:-my-2 [@media(hover:none)]:-mr-2 [@media(hover:none)]:h-10 [@media(hover:none)]:w-10 focus-visible:opacity-100 sm:[@media(hover:hover)]:opacity-40 sm:group-hover:opacity-100"
                            title="Odpísať {{ $assignment->user }}"
                            aria-label="Odpísať {{ $assignment->user }} – {{ $this->dayName }} {{ $this->shortDate }}"
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
                    {{ $this->countLabel }}
                </span>
            </div>
        @endif

        {{-- Manager-only, shown by the "losovanie" toggle (see app.css .show-draw) --}}
        @if($this->canLock)
            <div class="draw-list border-t border-neutral-800 px-3 py-2">
                <p class="text-xs font-medium text-neutral-300">
                    K&nbsp;dispozícii na losovanie ({{ count($this->drawable) }})
                </p>
                <p class="mt-1 text-xs leading-relaxed text-neutral-400">
                    @forelse($this->drawable as $name)
                        <span class="whitespace-nowrap">{{ $name }}</span>{{ $loop->last ? '' : ',' }}
                    @empty
                        Nikto ďalší nie je voľný.
                    @endforelse
                </p>
            </div>
        @endif
    </div>
</div>
