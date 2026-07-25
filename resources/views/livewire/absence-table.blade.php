<div>
    <div class="bg-neutral-900 border border-neutral-800 shadow-2xl rounded-xl overflow-hidden">
        {{-- Header Filter Tabs & Search Bar --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-5 bg-neutral-900/80 border-b border-neutral-800">
            {{-- Tabs --}}
            <div class="flex items-center gap-1 bg-neutral-800/80 p-1 rounded-lg border border-neutral-700/80">
                <button
                    type="button"
                    wire:click="setTab('active')"
                    @class([
                        'px-3 py-1.5 text-xs font-semibold rounded-md transition',
                        'bg-sky-500/20 text-sky-300 border border-sky-500/40 shadow-sm' => $tab === 'active',
                        'text-neutral-400 hover:text-white' => $tab !== 'active',
                    ])
                >
                    Aktívne
                </button>

                <button
                    type="button"
                    wire:click="setTab('mine')"
                    @class([
                        'px-3 py-1.5 text-xs font-semibold rounded-md transition',
                        'bg-sky-500/20 text-sky-300 border border-sky-500/40 shadow-sm' => $tab === 'mine',
                        'text-neutral-400 hover:text-white' => $tab !== 'mine',
                    ])
                >
                    Moje absencie
                </button>

                <button
                    type="button"
                    wire:click="setTab('past')"
                    @class([
                        'px-3 py-1.5 text-xs font-semibold rounded-md transition',
                        'bg-sky-500/20 text-sky-300 border border-sky-500/40 shadow-sm' => $tab === 'past',
                        'text-neutral-400 hover:text-white' => $tab !== 'past',
                    ])
                >
                    Vypršané
                </button>

                @if($this->isAdmin)
                    <button
                        type="button"
                        wire:click="setTab('all')"
                        @class([
                            'px-3 py-1.5 text-xs font-semibold rounded-md transition',
                            'bg-sky-500/20 text-sky-300 border border-sky-500/40 shadow-sm' => $tab === 'all',
                            'text-neutral-400 hover:text-white' => $tab !== 'all',
                        ])
                    >
                        Všetky
                    </button>
                @endif
            </div>

            {{-- Search Input --}}
            <div class="relative w-full sm:w-72">
                <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none text-neutral-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    class="block w-full rounded-lg border border-neutral-700 bg-neutral-800 p-2.5 ps-10 text-sm text-neutral-100 placeholder-neutral-400 transition focus:border-neutral-500 focus:outline-none"
                    placeholder="Vyhľadať absenciu..."
                />
                @if(!blank($search))
                    <button
                        type="button"
                        wire:click="$set('search', '')"
                        class="absolute inset-y-0 end-0 flex items-center pe-3 text-neutral-400 hover:text-white"
                    >
                        <i class="fa-solid fa-xmark text-xs"></i>
                    </button>
                @endif
            </div>
        </div>

        {{-- Table --}}
        <div class="relative overflow-x-auto">
            <table class="w-full text-sm text-left text-neutral-300">
                <thead class="text-xs uppercase tracking-wider text-neutral-400 bg-neutral-900 border-b border-neutral-800">
                    <tr>
                        <th class="px-6 py-3 font-semibold">Meno</th>
                        <th class="px-6 py-3 font-semibold">Začiatok</th>
                        <th class="px-6 py-3 font-semibold">Koniec</th>
                        <th class="px-6 py-3 font-semibold">Stav</th>
                        <th class="px-6 py-3 font-semibold">Dôvod</th>
                        <th class="px-6 py-3 font-semibold text-center">Akcie</th>
                    </tr>
                </thead>
                <tbody wire:loading.class="opacity-50 transition-opacity duration-300" class="divide-y divide-neutral-800/80">
                    @forelse($absences as $absence)
                        @php
                            $isMine = $absence->user_id === auth()->id();
                            $isActive = $absence->date_to->gte(now()->startOfDay());
                        @endphp
                        <x-table-row>
                            <x-table-cell-header class="px-6 py-4 font-bold whitespace-nowrap text-white">
                                {{ $absence->user }}
                            </x-table-cell-header>

                            <x-table-cell class="whitespace-nowrap text-neutral-300">
                                {{ $absence->date_from->format('d.m.Y') }}
                            </x-table-cell>

                            <x-table-cell class="whitespace-nowrap text-neutral-300">
                                {{ $absence->isOpenEnded() ? 'Trvalá' : $absence->date_to->format('d.m.Y') }}
                            </x-table-cell>

                            <x-table-cell>
                                <span @class([
                                    'inline-flex items-center gap-1.5 px-3 py-1 text-xs font-semibold rounded-full border',
                                    'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' => $isActive,
                                    'bg-neutral-800 text-neutral-400 border-neutral-700' => ! $isActive,
                                ])>
                                    {{ $isActive ? 'Aktívna' : 'Vypršaná' }}
                                </span>
                            </x-table-cell>

                            <x-table-cell class="max-w-xs truncate text-neutral-300" title="{{ $absence->reason }}">
                                {{ $absence->reason ?: '—' }}
                            </x-table-cell>

                            <x-table-cell class="text-center">
                                <div class="flex items-center justify-center gap-2">
                                    @if(($isMine || $this->isAdmin) && $isActive)
                                        <button
                                            type="button"
                                            wire:click="endAbsence({{ $absence->id }})"
                                            title="Ukončiť absenciu"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium rounded-lg bg-amber-500/15 text-amber-400 border border-amber-500/30 hover:bg-amber-500/30 hover:text-white transition"
                                        >
                                            <i class="fa-solid fa-xmark text-xs"></i>
                                            Ukončiť
                                        </button>
                                    @endif

                                    @if($isMine || $this->isAdmin)
                                        <button
                                            type="button"
                                            wire:click="deleteAbsence({{ $absence->id }})"
                                            title="Vymazať absenciu"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-rose-500/15 text-rose-400 border border-rose-500/30 hover:bg-rose-500/30 hover:text-white transition"
                                        >
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    @endif
                                </div>
                            </x-table-cell>
                        </x-table-row>
                    @empty
                        <x-table-row>
                            <x-table-cell colspan="6" class="!p-0">
                                <x-empty-state message="Žiadne absencie." icon="fa-calendar-xmark" class="rounded-none border-none bg-transparent py-12">
                                    Momentálne tu nie sú žiadne záznamy o absenciách pre zvolený filter.
                                </x-empty-state>
                            </x-table-cell>
                        </x-table-row>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($absences->hasPages())
            <div class="p-4 bg-neutral-900 border-t border-neutral-800">
                {{ $absences->links('vendor.pagination.tailwind') }}
            </div>
        @endif
    </div>
</div>
