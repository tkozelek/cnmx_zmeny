<div>
    @if ($paginator->hasPages())
        @php(isset($this->numberOfPaginatorsRendered[$paginator->getPageName()]) ? $this->numberOfPaginatorsRendered[$paginator->getPageName()]++ : $this->numberOfPaginatorsRendered[$paginator->getPageName()] = 1)

        <nav role="navigation" aria-label="Navigácia stránkovania" class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
            {{-- Mobile pagination --}}
            <div class="flex justify-between w-full sm:hidden">
                @if ($paginator->onFirstPage())
                    <button type="button" aria-disabled="true" class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-400 bg-neutral-950 border border-neutral-800 rounded-lg cursor-default">
                        Predchádzajúca
                    </button>
                @else
                    <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-neutral-200 bg-neutral-800 border border-neutral-700 rounded-lg hover:bg-neutral-700 transition">
                        Predchádzajúca
                    </button>
                @endif

                @if ($paginator->hasMorePages())
                    <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" class="inline-flex items-center px-4 py-2 text-sm font-semibold text-neutral-200 bg-neutral-800 border border-neutral-700 rounded-lg hover:bg-neutral-700 transition">
                        Nasledujúca
                    </button>
                @else
                    <button type="button" aria-disabled="true" class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-400 bg-neutral-950 border border-neutral-800 rounded-lg cursor-default">
                        Nasledujúca
                    </button>
                @endif
            </div>

            {{-- Desktop pagination centered links --}}
            <div class="hidden sm:flex sm:items-center sm:justify-center w-full">
                <div class="inline-flex items-center justify-center gap-1.5 p-1 bg-neutral-900 border border-neutral-800 rounded-xl shadow-xl">
                    {{-- Previous Page Button --}}
                    @if ($paginator->onFirstPage())
                        <button type="button" aria-disabled="true" aria-label="Predchádzajúca stránka" class="flex h-9 w-9 items-center justify-center rounded-lg text-neutral-400 bg-neutral-950 border border-neutral-800/80 cursor-default">
                            <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
                        </button>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" x-on:click="window.tablePagerFocus = true" class="flex h-9 w-9 items-center justify-center rounded-lg text-neutral-300 hover:text-white bg-neutral-800 border border-neutral-700/80 transition hover:bg-neutral-700" title="Predchádzajúca stránka" aria-label="Predchádzajúca stránka">
                            <i class="fa-solid fa-chevron-left text-xs" aria-hidden="true"></i>
                        </button>
                    @endif

                    {{-- Page Numbers --}}
                    @if ($elements ?? null)
                        @foreach ($elements as $element)
                            @if (is_string($element))
                                <span class="flex h-9 min-w-9 px-2 items-center justify-center text-xs font-semibold text-neutral-400">
                                    {{ $element }}
                                </span>
                            @endif

                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    <span wire:key="paginator-{{ $paginator->getPageName() }}-{{ $this->numberOfPaginatorsRendered[$paginator->getPageName()] }}-page{{ $page }}">
                                        @if ($page == $paginator->currentPage())
                                            {{-- The table re-renders its pager, so focus is handed to the new current page. --}}
                                            <button type="button" aria-current="page" aria-disabled="true"
                                                    x-data x-init="if (window.tablePagerFocus) { window.tablePagerFocus = false; requestAnimationFrame(() => $el.focus({ preventScroll: true })) }" class="flex h-9 min-w-9 px-3 items-center justify-center rounded-lg text-xs font-bold text-brand-300 bg-brand-500/20 border border-brand-500/60 shadow cursor-default">
                                                {{ $page }}
                                            </button>
                                        @else
                                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" x-on:click="window.tablePagerFocus = true" class="flex h-9 min-w-9 px-3 items-center justify-center rounded-lg text-xs font-semibold text-neutral-300 hover:text-white hover:bg-neutral-800 transition">
                                                {{ $page }}
                                            </button>
                                        @endif
                                    </span>
                                @endforeach
                            @endif
                        @endforeach
                    @endif

                    {{-- Next Page Button --}}
                    @if ($paginator->hasMorePages())
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" x-on:click="window.tablePagerFocus = true" class="flex h-9 w-9 items-center justify-center rounded-lg text-neutral-300 hover:text-white bg-neutral-800 border border-neutral-700/80 transition hover:bg-neutral-700" title="Nasledujúca stránka" aria-label="Nasledujúca stránka">
                            <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
                        </button>
                    @else
                        <button type="button" aria-disabled="true" aria-label="Nasledujúca stránka" class="flex h-9 w-9 items-center justify-center rounded-lg text-neutral-400 bg-neutral-950 border border-neutral-800/80 cursor-default">
                            <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
                        </button>
                    @endif
                </div>
            </div>
        </nav>
    @endif
</div>
