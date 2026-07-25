@aware([ 'tableName','isTailwind','isBootstrap','isBootstrap4','isBootstrap5', 'localisationPath'])

@if ($isTailwind)
    <div>
        @if ($this->sortingPillsAreEnabled() && $this->hasSorts())
            <div class="mb-4 flex flex-wrap items-center gap-2" x-cloak x-show="!currentlyReorderingStatus">
                <span class="text-xs font-semibold text-neutral-400">Aplikované zoradenie:</span>

                @foreach($this->getSorts() as $columnSelectName => $direction)
                    @php($column = $this->getColumnBySelectName($columnSelectName) ?? $this->getColumnBySlug($columnSelectName))

                    @continue(is_null($column))
                    @continue($column->isHidden())
                    @continue($this->columnSelectIsEnabled && ! $this->columnSelectIsEnabledForColumn($column))

                    <span
                        wire:key="{{ $tableName }}-sorting-pill-{{ $columnSelectName }}"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-300 border border-sky-500/30"
                    >
                        <span>{{ $column->getSortingPillTitle() }}: {{ $direction === 'asc' ? 'A-Z (vzostupne)' : 'Z-A (zostupne)' }}</span>

                        <button
                            wire:click="clearSort('{{ $columnSelectName }}')"
                            type="button"
                            class="flex-shrink-0 h-4 w-4 rounded-full inline-flex items-center justify-center text-sky-400 hover:text-white focus:outline-none"
                            title="Zrušiť zoradenie"
                        >
                            <i class="fa-solid fa-xmark text-xs"></i>
                        </button>
                    </span>
                @endforeach

                <button
                    wire:click.prevent="clearSorts"
                    type="button"
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-neutral-800 text-neutral-300 hover:bg-neutral-700 hover:text-white transition"
                >
                    Zrušiť všetko
                </button>
            </div>
        @endif
    </div>
@endif
