<div class="flex flex-col gap-6">

    @if($this->canCreate)
        {{-- One form serves create and edit: editingId decides which. --}}
        <div class="rounded-2xl border border-neutral-800 bg-neutral-900/90 p-5 shadow-xl sm:p-6">
            <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-sky-400">
                <i class="fa-solid {{ $editingId ? 'fa-pen-to-square' : 'fa-plus' }} text-xs"></i>
                {{ $editingId ? 'Upraviť pozíciu' : 'Nová pozícia' }}
            </h2>

            <form wire:submit="save" class="flex flex-col gap-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div>
                        <label for="position-name" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-neutral-400">Názov</label>
                        <input id="position-name" type="text" wire:model="name" placeholder="Bufet"
                               class="w-full rounded-xl border border-neutral-800 bg-neutral-900 px-4 py-3 text-sm text-white placeholder-neutral-500 shadow-inner transition hover:border-neutral-700 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/60">
                        @error('name') <p class="mt-1.5 text-xs font-medium text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="position-code" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-neutral-400">Skratka</label>
                        <input id="position-code" type="text" wire:model="code" placeholder="BUF" maxlength="10"
                               class="w-full rounded-xl border border-neutral-800 bg-neutral-900 px-4 py-3 text-sm text-white placeholder-neutral-500 shadow-inner transition hover:border-neutral-700 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/60">
                        @error('code') <p class="mt-1.5 text-xs font-medium text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="position-group" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-neutral-400">Skupina</label>
                        <select id="position-group" wire:model="groupId"
                                class="h-[46px] w-full rounded-xl border border-neutral-800 bg-neutral-900 px-4 text-sm text-white shadow-inner transition hover:border-neutral-700 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/60">
                            <option value="">- bez skupiny -</option>
                            @foreach($this->groups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                        @error('groupId') <p class="mt-1.5 text-xs font-medium text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="position-color" class="mb-2 block text-xs font-semibold uppercase tracking-wider text-neutral-400">Farba</label>
                        <input id="position-color" type="color" wire:model="color"
                               class="h-[46px] w-full cursor-pointer rounded-xl border border-neutral-800 bg-neutral-900 px-2 shadow-inner">
                        @error('color') <p class="mt-1.5 text-xs font-medium text-rose-400">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-end">
                        <label class="flex min-h-[46px] w-full cursor-pointer items-center gap-2.5 rounded-xl border border-neutral-800 bg-neutral-900 px-4 text-sm text-neutral-200">
                            <input type="checkbox" wire:model="isManager"
                                   class="h-4 w-4 rounded border-neutral-700 bg-neutral-800 text-sky-500 focus:ring-0">
                            Vedúci zmeny
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    @if($editingId)
                        <button type="button" wire:click="cancelEdit"
                                class="rounded-xl border border-neutral-700 px-4 py-2.5 text-xs font-bold uppercase tracking-widest text-neutral-300 transition hover:bg-neutral-800 hover:text-white">
                            Zrušiť
                        </button>
                    @endif

                    <button type="submit" wire:loading.attr="disabled"
                            class="flex items-center gap-2 rounded-xl border border-neutral-700 bg-neutral-800 px-5 py-2.5 text-xs font-bold uppercase tracking-widest text-white shadow-lg transition hover:border-sky-500/50 disabled:opacity-50">
                        <span>{{ $editingId ? 'Uložiť' : 'Pridať' }}</span>
                        <i class="fa-solid fa-floppy-disk text-xs text-sky-400"></i>
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Groups. Their order is the order the rozpis and the Excel print them in, which is why
         they get arrows of their own rather than being sorted alphabetically. --}}
    @if($this->canCreate)
        <div class="rounded-2xl border border-neutral-800 bg-neutral-900/90 p-5 shadow-xl sm:p-6">
            <h2 class="mb-1 flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-sky-400">
                <i class="fa-solid fa-layer-group text-xs"></i>
                Skupiny pozícií
            </h2>
            <p class="mb-4 text-xs text-neutral-400">
                Napríklad Bufet, Uvádzač, Manažment. Pozície tej istej skupiny sa v rozpise aj v Exceli
                zobrazia pod sebou. Poradie skupín určuje poradie na výslednom hárku.
            </p>

            <form wire:submit="saveGroup" class="flex flex-col gap-2 sm:flex-row sm:items-start">
                <div class="flex-1">
                    <input type="text" wire:model="groupName" placeholder="Názov skupiny (napr. Bufet)"
                           class="w-full rounded-xl border border-neutral-800 bg-neutral-900 px-4 py-3 text-sm text-white placeholder-neutral-500 shadow-inner transition hover:border-neutral-700 focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-500/60">
                    @error('groupName') <p class="mt-1.5 text-xs font-medium text-rose-400">{{ $message }}</p> @enderror
                </div>

                @if($editingGroupId)
                    <button type="button" wire:click="cancelGroupEdit"
                            class="rounded-xl border border-neutral-700 px-4 py-3 text-xs font-bold uppercase tracking-widest text-neutral-300 transition hover:bg-neutral-800 hover:text-white">
                        Zrušiť
                    </button>
                @endif

                <button type="submit" wire:loading.attr="disabled"
                        class="flex items-center justify-center gap-2 rounded-xl border border-neutral-700 bg-neutral-800 px-5 py-3 text-xs font-bold uppercase tracking-widest text-white shadow-lg transition hover:border-sky-500/50 disabled:opacity-50">
                    <span>{{ $editingGroupId ? 'Premenovať' : 'Pridať skupinu' }}</span>
                    <i class="fa-solid {{ $editingGroupId ? 'fa-pen-to-square' : 'fa-plus' }} text-xs text-sky-400"></i>
                </button>
            </form>

            @if($this->groups->isNotEmpty())
                <ul class="mt-4 flex flex-col divide-y divide-neutral-800 border-t border-neutral-800">
                    @foreach($this->groups as $index => $group)
                        <li wire:key="group-{{ $group->id }}" class="flex items-center gap-2 py-2">
                            <span class="flex items-center gap-1">
                                <button type="button" wire:click="moveGroup({{ $group->id }}, -1)"
                                        @disabled($index === 0) title="Posunúť vyššie"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg border border-neutral-700 bg-neutral-800 text-neutral-300 transition hover:text-white disabled:opacity-30">
                                    <i class="fa-solid fa-chevron-up text-[0.65rem]"></i>
                                </button>
                                <button type="button" wire:click="moveGroup({{ $group->id }}, 1)"
                                        @disabled($index === $this->groups->count() - 1) title="Posunúť nižšie"
                                        class="flex h-7 w-7 items-center justify-center rounded-lg border border-neutral-700 bg-neutral-800 text-neutral-300 transition hover:text-white disabled:opacity-30">
                                    <i class="fa-solid fa-chevron-down text-[0.65rem]"></i>
                                </button>
                            </span>

                            <span class="min-w-0 flex-1 truncate text-sm font-semibold text-white">{{ $group->name }}</span>

                            <span class="shrink-0 rounded-full border border-neutral-700 bg-neutral-800 px-2.5 py-0.5 text-[0.65rem] font-semibold text-neutral-400">
                                {{ $group->positions_count }} {{ $group->positions_count === 1 ? 'pozícia' : 'pozícií' }}
                            </span>

                            <button type="button" wire:click="editGroup({{ $group->id }})" title="Premenovať skupinu"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-neutral-700 bg-neutral-800 text-neutral-300 transition hover:text-white">
                                <i class="fa-solid fa-pen-to-square text-[0.65rem]"></i>
                            </button>

                            {{-- Safe to delete outright: positions survive, merely unfiled. --}}
                            <button type="button" wire:click="deleteGroup({{ $group->id }})"
                                    wire:confirm="Zmazať skupinu {{ $group->name }}? Pozície ostanú zachované, len bez skupiny."
                                    title="Zmazať skupinu"
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-rose-500/30 bg-rose-500/10 text-rose-300 transition hover:bg-rose-500/20">
                                <i class="fa-solid fa-trash text-[0.65rem]"></i>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    @endif

    {{-- Inactive rows stay listed: that is what "deaktivovaná, nie zmazaná" means. --}}
    <x-table :headers="['Poradie', 'Skupina', 'Názov', 'Skratka', 'Vedúci', 'Stav', 'Akcie']">
        @forelse($this->positions as $index => $position)
            <x-table-row wire:key="position-{{ $position->id }}">
                <x-table-cell>
                    <div class="flex items-center gap-1">
                        <button type="button" wire:click="move({{ $position->id }}, -1)"
                                @disabled($index === 0)
                                title="Posunúť vyššie"
                                class="flex h-7 w-7 items-center justify-center rounded-lg border border-neutral-700 bg-neutral-800 text-neutral-300 transition hover:text-white disabled:opacity-30">
                            <i class="fa-solid fa-chevron-up text-[0.65rem]"></i>
                        </button>
                        <button type="button" wire:click="move({{ $position->id }}, 1)"
                                @disabled($index === $this->positions->count() - 1)
                                title="Posunúť nižšie"
                                class="flex h-7 w-7 items-center justify-center rounded-lg border border-neutral-700 bg-neutral-800 text-neutral-300 transition hover:text-white disabled:opacity-30">
                            <i class="fa-solid fa-chevron-down text-[0.65rem]"></i>
                        </button>
                    </div>
                </x-table-cell>

                <x-table-cell>
                    @if($position->group)
                        <span class="inline-flex items-center rounded-full border border-neutral-700 bg-neutral-800 px-2.5 py-1 text-xs font-semibold text-neutral-300">
                            {{ $position->group->name }}
                        </span>
                    @else
                        <span class="text-neutral-600">-</span>
                    @endif
                </x-table-cell>

                <x-table-cell class="font-semibold text-white">
                    <span class="flex items-center gap-2">
                        @if($position->color)
                            <span class="h-3 w-3 shrink-0 rounded-full border border-neutral-700" style="background-color: {{ $position->color }}"></span>
                        @endif
                        <span @class(['text-neutral-500 line-through' => ! $position->is_active])>{{ $position->name }}</span>
                    </span>
                </x-table-cell>

                <x-table-cell class="text-neutral-400">{{ $position->code ?: '-' }}</x-table-cell>

                <x-table-cell>
                    @if($position->is_manager)
                        <span class="inline-flex items-center gap-1.5 rounded-full border border-amber-500/30 bg-amber-500/10 px-3 py-1 text-xs font-semibold text-amber-400">
                            <i class="fa-solid fa-star text-[0.6rem]"></i> Vedúci
                        </span>
                    @else
                        <span class="text-neutral-600">-</span>
                    @endif
                </x-table-cell>

                <x-table-cell>
                    <span @class([
                        'inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold',
                        'border-emerald-500/30 bg-emerald-500/10 text-emerald-400' => $position->is_active,
                        'border-neutral-700 bg-neutral-800 text-neutral-400' => ! $position->is_active,
                    ])>
                        {{ $position->is_active ? 'Aktívna' : 'Neaktívna' }}
                    </span>
                </x-table-cell>

                <x-table-cell>
                    <div class="flex items-center justify-center gap-2">
                        <button type="button" wire:click="edit({{ $position->id }})"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-neutral-700 bg-neutral-800 px-3 py-1.5 text-xs font-semibold text-neutral-200 transition hover:bg-neutral-700 hover:text-white">
                            <i class="fa-solid fa-pen-to-square text-xs text-neutral-400"></i>
                            Upraviť
                        </button>

                        <button type="button" wire:click="toggleActive({{ $position->id }})"
                                wire:confirm="{{ $position->is_active ? 'Deaktivovať pozíciu '.$position->name.'? Prestane sa ponúkať v rozpise.' : 'Znovu aktivovať pozíciu '.$position->name.'?' }}"
                                @class([
                                    'inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition',
                                    'border-rose-500/30 bg-rose-500/10 text-rose-300 hover:bg-rose-500/20' => $position->is_active,
                                    'border-emerald-500/30 bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/20' => ! $position->is_active,
                                ])>
                            <i class="fa-solid {{ $position->is_active ? 'fa-ban' : 'fa-rotate-left' }} text-xs"></i>
                            {{ $position->is_active ? 'Deaktivovať' : 'Aktivovať' }}
                        </button>
                    </div>
                </x-table-cell>
            </x-table-row>
        @empty
            <x-table-row>
                <x-table-cell colspan="7" class="!p-0">
                    <x-empty-state message="Zatiaľ nie sú definované žiadne pozície." icon="fa-list-check" class="rounded-none border-none bg-transparent py-12">
                        Pridajte prvú pozíciu (napr. Bufet alebo Pokladňa), aby ste mohli zostaviť rozpis.
                    </x-empty-state>
                </x-table-cell>
            </x-table-row>
        @endforelse
    </x-table>
</div>
