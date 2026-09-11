<div @class([
    'flex flex-col overflow-hidden rounded-md border bg-neutral-900 shadow-sm transition',
    'border-amber-500/40 ring-1 ring-amber-500/20' => $this->isHardToStaff,
    'border-emerald-500/40 ring-1 ring-emerald-500/20' => $this->isDesirableDay,
    'border-neutral-800' => ! $this->isHardToStaff && ! $this->isDesirableDay,
])>
    <x-rozpis.day-heading :name="$this->dayName" :date="$this->dayCarbon">
        @if($this->rows)
            <p @class([
                'mt-1 text-[0.7rem] font-semibold',
                'text-emerald-400' => $this->filledCount === count($this->rows),
                'text-neutral-400' => $this->filledCount < count($this->rows),
            ])>
                {{ $this->filledCount }}/{{ count($this->rows) }} obsadené
            </p>
        @endif

        @if($this->isHardToStaff)
            <span class="mt-1.5 inline-flex items-center gap-1 rounded-full border border-amber-500/30 bg-amber-500/10 px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wider text-amber-400"
                  title="Málokto sa naň hlási dobrovoľne. Zoznam nezaradených je preto zoradený podľa toho, kto je na ťahu.">
                <i class="fa-solid fa-triangle-exclamation text-[0.55rem]"></i> Neobľúbený deň
            </span>
        @elseif($this->isDesirableDay)
            <span class="mt-1.5 inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wider text-emerald-400"
                  title="Obľúbený deň. Zoznam nezaradených je preto zoradený tak, aby bol hore ten, kto si ho najviac zaslúži za odrobené neobľúbené dni.">
                Obľúbený deň
            </span>
        @endif
    </x-rozpis.day-heading>

    {{-- Position rows: draggable to reorder, and each one a drop target for a person. --}}
    <div class="flex flex-col gap-2 px-3 py-2.5"
         @if($this->canBuild) x-sortable-order="reorderSlots" @endif>
        @forelse($this->rows as $row)
            {{-- Group heading. Sits outside the draggable card so SortableJS never picks it up,
                 and rows sort back into their own group anyway (RozpisService::sortSlots). --}}
            @if($row['startsGroup'])
                <p class="mt-1 truncate px-0.5 text-[0.6rem] font-bold uppercase tracking-widest text-neutral-500 first:mt-0">
                    {{ $row['group'] }}
                </p>
            @endif

            <div wire:key="slot-{{ $row['slot']->id }}"
                 data-slot-id="{{ $row['slot']->id }}"
                 class="rounded-md border border-neutral-800 bg-neutral-950/40">
                <div class="flex items-center justify-between gap-2 border-b border-neutral-800/70 px-2 py-1.5">
                    <span class="flex min-w-0 items-center gap-1.5">
                        @if($this->canBuild)
                            <span data-drag-handle
                                  title="Presuňte pre zmenu poradia"
                                  class="shrink-0 cursor-grab px-0.5 text-neutral-600 transition hover:text-neutral-300 active:cursor-grabbing">
                                <i class="fa-solid fa-grip-vertical text-[0.7rem]"></i>
                            </span>
                        @endif

                        @if($row['slot']->position->color)
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $row['slot']->position->color }}"></span>
                        @endif

                        <span class="truncate text-sm font-semibold text-neutral-200" title="{{ $row['slot']->position->name }}">
                            {{ $row['label'] }}
                        </span>

                        @if($this->canBuild)
                            {{-- Editable in place: the schedule moves and the bufet opens later.
                                 Saves on change, so there is no form and no submit button. --}}
                            <div x-data="slotTimePicker({{ $row['slot']->id }}, @js($row['time']))" class="shrink-0">
                                <input x-ref="input" type="text" readonly
                                       placeholder="+ čas" title="Čas nástupu - kliknutím zmeníte"
                                       class="w-[3.75rem] cursor-pointer rounded bg-neutral-800/80 px-1.5 py-0.5 text-center text-[0.7rem] font-semibold tabular-nums text-neutral-300 transition hover:bg-neutral-700 hover:text-white focus:outline-none focus:ring-1 focus:ring-sky-500">
                            </div>
                        @elseif($row['time'])
                            <span class="shrink-0 rounded bg-neutral-800/80 px-1.5 py-0.5 text-[0.7rem] font-semibold tabular-nums text-neutral-300">
                                {{ $row['time'] }}
                            </span>
                        @endif
                    </span>

                    @if($this->canBuild)
                        <span class="flex shrink-0 items-center gap-0.5">
                            {{-- Copy just this one row onto other days. Alpine holds the checkbox
                                 state, so there is no per-slot array to keep on the server. --}}
                            @if($this->copyTargets)
                                <div x-data="{ open: false, days: [] }" class="relative">
                                    <button type="button" @click="open = ! open"
                                            title="Skopírovať túto pozíciu do iných dní"
                                            class="flex h-5 w-5 items-center justify-center rounded text-neutral-500 transition hover:bg-sky-500/20 hover:text-sky-300">
                                        <i class="fa-solid fa-clone text-[0.65rem]"></i>
                                    </button>

                                    <div x-show="open" x-cloak @click.outside="open = false"
                                         class="absolute right-0 z-20 mt-1 w-44 rounded-md border border-neutral-700 bg-neutral-900 p-2 text-left shadow-lg">
                                        <p class="pb-1 text-[0.65rem] font-semibold uppercase tracking-wider text-neutral-500">
                                            Kopírovať {{ $row['label'] }} do
                                        </p>

                                        @foreach($this->copyTargets as $target)
                                            <label class="flex cursor-pointer items-center gap-2 py-0.5 text-xs text-neutral-300 hover:text-white">
                                                <input type="checkbox" x-model="days" value="{{ $target['date'] }}"
                                                       class="h-3.5 w-3.5 rounded border-neutral-600 bg-neutral-800 text-sky-500 focus:ring-0">
                                                {{ $target['label'] }}
                                            </label>
                                        @endforeach

                                        <button type="button"
                                                @click="$wire.copySlot({{ $row['slot']->id }}, days); days = []; open = false"
                                                class="mt-1.5 w-full rounded border border-sky-500/40 bg-sky-500/10 px-2 py-1 text-[0.7rem] font-semibold text-sky-300 transition hover:bg-sky-500/20">
                                            Kopírovať
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <button type="button"
                                    wire:click="removeSlot({{ $row['slot']->id }})"
                                    wire:confirm="Odobrať {{ $row['label'] }} z tohto dňa?"
                                    title="Odobrať pozíciu z dňa"
                                    class="flex h-5 w-5 items-center justify-center rounded text-neutral-500 transition hover:bg-rose-500/20 hover:text-rose-300">
                                <i class="fa-solid fa-xmark text-[0.7rem]"></i>
                            </button>
                        </span>
                    @endif
                </div>

                {{-- Drop zone. The minimum height keeps an empty row a visible target. --}}
                <div class="min-h-[2.5rem] p-1.5"
                     x-sortable="place"
                     data-sortable-group="{{ $this->dragGroup }}"
                     data-sortable-target="{{ $row['slot']->id }}">
                    @if($row['occupant'])
                        <div wire:key="placed-{{ $row['occupant']->id }}"
                             data-sortable-id="{{ $row['occupant']->id }}"
                             class="group flex cursor-grab items-center justify-between gap-1.5 rounded bg-neutral-800/80 px-2 py-1.5 text-sm text-neutral-100">
                            <span class="truncate font-semibold">{{ $row['occupant']->user }}</span>

                            @if($this->canBuild)
                                <button type="button"
                                        wire:click="unplace({{ $row['occupant']->id }})"
                                        title="Vrátiť do zoznamu"
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded text-neutral-400 transition hover:bg-rose-500/20 hover:text-rose-300 focus:opacity-100 sm:opacity-0 sm:group-hover:opacity-100">
                                    <i class="fa-solid fa-arrow-turn-down text-[0.65rem]"></i>
                                </button>
                            @endif
                        </div>
                    @elseif($row['suggestion'])
                        {{-- AI draft: dashed and muted until the manager accepts it. Nothing is
                             written to `assignments` while it looks like this. --}}
                        <div class="flex items-center justify-between gap-1.5 rounded border border-dashed border-violet-500/50 bg-violet-500/5 px-2 py-1 text-sm">
                            <span class="min-w-0 truncate text-violet-300/90" title="AI návrh - nie je uložené">
                                <i class="fa-solid fa-wand-magic-sparkles text-[0.6rem] opacity-70"></i>
                                {{ $row['suggestion']->user }}
                            </span>

                            <button type="button"
                                    wire:click="acceptSuggestion({{ $row['suggestion']->id }})"
                                    title="Prijať návrh"
                                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded text-emerald-400 transition hover:bg-emerald-500/20 hover:text-emerald-300">
                                <i class="fa-solid fa-check text-[0.65rem]"></i>
                            </button>
                        </div>
                    @elseif($this->canBuild && $row['slot']->position->is_manager && $this->leadershipRoster->isNotEmpty())
                        {{-- The vedúci row picks from who may lead a shift, not from who signed
                             up: leadership does not write itself into the daily pool. --}}
                        <select wire:change="placeLeader($event.target.value, {{ $row['slot']->id }})"
                                class="w-full rounded border border-violet-500/30 bg-neutral-900 px-1.5 py-1 text-xs text-neutral-400 focus:border-sky-500 focus:outline-none">
                            <option value="">- vybrať vedúceho -</option>
                            @foreach($this->leadershipRoster as $leader)
                                <option value="{{ $leader->id }}">{{ $leader }}</option>
                            @endforeach
                        </select>
                    @elseif($this->canBuild && $this->unassignedPool->isNotEmpty())
                        {{-- Keyboard and touch path to the same server method the drag calls. The
                             drag pool shows the score as a coloured badge next to the name; a
                             <select> cannot render that, so on the two day types where the number
                             means something it is appended to the option text instead - otherwise
                             touch users would build blind to the one thing that decides the order. --}}
                        <select wire:change="place($event.target.value, {{ $row['slot']->id }})"
                                @if($this->isHardToStaff)
                                    title="Zoradené podľa spravodlivosti - vyššie číslo znamená, že tento človek odpracoval menej ťažkých dní, než je v kine zvykom, a je na rade."
                                @elseif($this->isDesirableDay)
                                    title="Zoradené podľa spravodlivosti - vyššie číslo znamená viac odpracovaných a ťažších dní, takže si tento deň zaslúži viac."
                                @endif
                                class="w-full rounded border border-neutral-800 bg-neutral-900 px-1.5 py-1 text-xs text-neutral-400 focus:border-sky-500 focus:outline-none">
                            <option value="">- priradiť -</option>
                            @foreach($this->unassignedPool as $candidate)
                                <option value="{{ $candidate->id }}">
                                    {{ $candidate->user }}
                                    @if($this->rankingKey)
                                        ({{ number_format($this->scoreFor($candidate->user_id), 1) }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    @else
                        <p class="px-1 text-[0.7rem] italic text-neutral-600">Prázdne</p>
                    @endif
                </div>
            </div>
        @empty
            <p class="py-2 text-center text-xs italic text-neutral-500">
                Žiadne pozície pre tento deň.
            </p>
        @endforelse
    </div>

    {{-- AI draft controls. Hidden entirely when no API key is configured or all non-manager slots are filled. --}}
    @if($this->canBuild && $this->aiEnabled && $this->rows && ($suggestions || $this->hasUnfilledNonManagerSlots))
        <div class="flex items-center gap-1 border-t border-neutral-800/80 px-2.5 py-2">
            @if($suggestions)
                <button type="button" wire:click="acceptAllSuggestions"
                        class="flex flex-1 items-center justify-center gap-1.5 rounded border border-emerald-500/40 bg-emerald-500/10 px-2 py-1 text-[0.7rem] font-semibold text-emerald-300 transition hover:bg-emerald-500/20">
                    <i class="fa-solid fa-check-double text-[0.65rem]"></i>
                    Prijať všetky ({{ count($suggestions) }})
                </button>

                <button type="button" wire:click="dismissSuggestions" title="Zahodiť návrh"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded border border-neutral-700 bg-neutral-800 text-neutral-400 transition hover:text-white">
                    <i class="fa-solid fa-xmark text-[0.7rem]"></i>
                </button>
            @else
                <button type="button" wire:click="suggest" wire:loading.attr="disabled" wire:target="suggest"
                        class="flex w-full items-center justify-center gap-1.5 rounded border border-violet-500/40 bg-violet-500/10 px-2 py-1 text-[0.7rem] font-semibold text-violet-300 transition hover:bg-violet-500/20 disabled:opacity-50">
                    <span wire:loading.remove wire:target="suggest" class="flex items-center gap-1.5">
                        <i class="fa-solid fa-wand-magic-sparkles text-[0.65rem]"></i>
                        AI navrhni rozpis
                    </span>
                    <span wire:loading wire:target="suggest"><i class="fa-solid fa-spinner fa-spin"></i></span>
                </button>
            @endif
        </div>
    @endif

    {{-- Add a position to this day. The same position may be added more than once - bufet 1, 2, 3. --}}
    @if($this->canBuild && $this->availablePositions->isNotEmpty())
        <form wire:submit="addSlot" class="flex items-center gap-1 border-t border-neutral-800/80 px-2.5 py-2">
            <select wire:model="newPositionId"
                    class="min-w-0 flex-1 rounded border border-neutral-800 bg-neutral-900 px-1.5 py-1 text-xs text-neutral-300 focus:border-sky-500 focus:outline-none">
                <option value="">+ pozícia</option>
                @foreach($this->availablePositions as $position)
                    <option value="{{ $position->id }}">{{ $position->label() }}</option>
                @endforeach
            </select>

            {{-- flatpickr, not <input type="time">: the native control renders AM/PM from the OS
                 locale and no HTML attribute can force 24-hour. --}}
            <div x-data="timePicker('newStartTime', @js($newStartTime))" class="shrink-0">
                <input x-ref="input" type="text" readonly placeholder="čas" title="Čas nástupu"
                       class="w-[5rem] cursor-pointer rounded border border-neutral-800 bg-neutral-900 px-2 py-1.5 text-center text-xs tabular-nums text-neutral-300 focus:border-sky-500 focus:outline-none">
            </div>

            <button type="submit" wire:loading.attr="disabled" title="Pridať pozíciu"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded border border-neutral-700 bg-neutral-800 text-neutral-300 transition hover:text-white disabled:opacity-50">
                <i class="fa-solid fa-plus text-[0.7rem]"></i>
            </button>
        </form>

        @error('newPositionId') <p class="px-2.5 pb-1 text-[0.7rem] text-rose-400">{{ $message }}</p> @enderror
        @error('newStartTime') <p class="px-2.5 pb-1 text-[0.7rem] text-rose-400">{{ $message }}</p> @enderror
    @endif

    {{-- Unplaced signups --}}
    <div class="border-t border-neutral-800/80 bg-neutral-900/60">
        <p class="px-2.5 pt-2 text-[0.65rem] font-semibold uppercase tracking-wider text-neutral-500">
            Nezaradení ({{ $this->unassignedPool->count() }})
        </p>

        <div class="flex min-h-[2.5rem] flex-col gap-1 px-2.5 py-2"
             x-sortable="place"
             data-sortable-group="{{ $this->dragGroup }}">
            @forelse($this->unassignedPool as $assignment)
                <div wire:key="pool-{{ $assignment->id }}"
                     data-sortable-id="{{ $assignment->id }}"
                     title="Odpracované dni: {{ $this->statsFor($assignment->user_id)['totalDays'] }} · priemerná váha dňa: {{ number_format($this->statsFor($assignment->user_id)['avgWeight'], 2) }}"
                     class="flex cursor-grab items-center justify-between gap-1.5 rounded border border-neutral-800 bg-neutral-800/50 px-2 py-1 text-sm text-neutral-200">
                    <span class="min-w-0 truncate">
                        {{ $assignment->user }}
                        @if($assignment->note)
                            <span class="text-xs text-neutral-500">({{ $assignment->note }})</span>
                        @endif
                    </span>

                    @if($this->isHardToStaff)
                        <span class="shrink-0 rounded bg-neutral-900 px-1.5 py-0.5 text-[0.65rem] font-semibold text-amber-400"
                              title="Kto je na ťahu - vyššie číslo znamená, že odpracoval menej ťažkých dní, než je v kine zvykom">
                            {{ number_format($this->scoreFor($assignment->user_id), 1) }}
                        </span>
                    @elseif($this->isDesirableDay)
                        <span class="shrink-0 rounded bg-neutral-900 px-1.5 py-0.5 text-[0.65rem] font-semibold text-emerald-400"
                              title="Kto si tento deň zaslúži - vyššie číslo znamená viac odpracovaných a ťažších dní">
                            {{ number_format($this->scoreFor($assignment->user_id), 1) }}
                        </span>
                    @endif
                </div>
            @empty
                <p class="text-[0.7rem] italic text-neutral-600">Všetci sú zaradení.</p>
            @endforelse
        </div>
    </div>

    {{-- Copy this whole day's layout from another day. Lives on the day card, not the parent page:
         the form needs a target day, and here it is unambiguous which one that is. --}}
    @if($this->canBuild)
        <form method="POST" action="{{ route('rozpis.copy', ['date' => $date]) }}"
              class="flex items-center gap-1 border-t border-neutral-800/80 px-2.5 py-2">
            @csrf
            <select name="source_date"
                    class="min-w-0 flex-1 rounded border border-neutral-800 bg-neutral-900 px-1.5 py-1 text-xs text-neutral-400 focus:border-sky-500 focus:outline-none">
                <option value="">kopírovať celý deň z…</option>
                @foreach($this->copySources as $source)
                    <option value="{{ $source['date'] }}">{{ $source['label'] }}</option>
                @endforeach
            </select>

            <button type="submit" title="Skopírovať všetky pozície z vybraného dňa"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded border border-neutral-700 bg-neutral-800 text-neutral-300 transition hover:text-white">
                <i class="fa-solid fa-copy text-[0.7rem]"></i>
            </button>
        </form>
    @endif
</div>
