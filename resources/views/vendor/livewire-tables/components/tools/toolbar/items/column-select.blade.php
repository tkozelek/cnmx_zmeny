@aware([ 'tableName','isTailwind','isBootstrap','isBootstrap4','isBootstrap5', 'localisationPath'])
@if ($isTailwind)
    <div class="@if ($this->getColumnSelectIsHiddenOnMobile()) hidden sm:block @elseif ($this->getColumnSelectIsHiddenOnTablet()) hidden md:block @endif mb-4 w-full md:w-auto md:mb-0 md:ml-2">
        <div
            x-data="{ open: false, childElementOpen: false }"
            @keydown.window.escape="if (!childElementOpen) { open = false }"
            x-on:click.away="if (!childElementOpen) { open = false }"
            class="inline-block relative w-full text-left md:w-auto"
            wire:key="{{ $tableName }}-column-select-button"
        >
            <div>
                <span class="rounded-lg shadow-sm">
                    <button
                        x-on:click="open = !open"
                        type="button"
                        class="inline-flex items-center justify-center gap-2 h-10 rounded-lg border border-neutral-700 px-4 bg-neutral-800 text-sm font-semibold text-neutral-100 hover:bg-neutral-700 transition focus:outline-none"
                        aria-haspopup="true"
                        x-bind:aria-expanded="open"
                    >
                        <i class="fa-solid fa-table-columns text-xs text-sky-400"></i>
                        <span>Stĺpce</span>
                        <i class="fa-solid fa-chevron-down text-xs text-neutral-500"></i>
                    </button>
                </span>
            </div>

            <div
                x-cloak x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 z-50 mt-2 w-56 rounded-xl border border-neutral-800 bg-neutral-900 p-2 shadow-2xl origin-top-right focus:outline-none"
            >
                <div class="space-y-1 text-sm text-neutral-200">
                    <div wire:key="{{ $tableName }}-columnSelect-selectAll-{{ rand(0,1000) }}">
                        <label class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 hover:bg-neutral-800 cursor-pointer font-semibold text-sky-400">
                            <input
                                type="checkbox"
                                class="rounded border-neutral-700 bg-neutral-800 text-sky-500 focus:ring-sky-500"
                                @checked($this->getSelectableSelectedColumns()->count() === $this->getSelectableColumns()->count())
                                @if($this->getSelectableSelectedColumns()->count() === $this->getSelectableColumns()->count())  wire:click="deselectAllColumns" @else wire:click="selectAllColumns" @endif
                            >
                            <span>Všetky stĺpce</span>
                        </label>
                    </div>

                    <div class="my-1 border-t border-neutral-800"></div>

                    @foreach ($this->getColumnsForColumnSelect() as $columnSlug => $columnTitle)
                        <div wire:key="{{ $tableName }}-columnSelect-{{ $loop->index }}">
                            <label class="flex items-center gap-2.5 rounded-lg px-2 py-1.5 hover:bg-neutral-800 cursor-pointer text-neutral-300">
                                <input
                                    wire:model.live="selectedColumns"
                                    type="checkbox"
                                    value="{{ $columnSlug }}"
                                    class="rounded border-neutral-700 bg-neutral-800 text-sky-500 focus:ring-sky-500"
                                />
                                <span>{{ $columnTitle }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endif
