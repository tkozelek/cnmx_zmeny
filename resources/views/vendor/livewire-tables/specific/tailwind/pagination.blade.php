<div>
    @if ($paginator->hasPages())
        @php(isset($this->numberOfPaginatorsRendered[$paginator->getPageName()]) ? $this->numberOfPaginatorsRendered[$paginator->getPageName()]++ : $this->numberOfPaginatorsRendered[$paginator->getPageName()] = 1)

        <nav role="navigation" aria-label="Navigácia stránkovania" class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
            {{-- Mobile pagination --}}
            <div class="flex justify-between w-full sm:hidden">
                @if ($paginator->onFirstPage())
                    <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-600 bg-neutral-950 border border-neutral-800 rounded-lg cursor-default">
                        Predchádzajúca
                    </span>
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
                    <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-neutral-600 bg-neutral-950 border border-neutral-800 rounded-lg cursor-default">
                        Nasledujúca
                    </span>
                @endif
            </div>

            {{-- Desktop pagination centered links --}}
            <div class="hidden sm:flex sm:items-center sm:justify-center w-full">
                <div class="inline-flex items-center justify-center gap-1.5 p-1 bg-neutral-900 border border-neutral-800 rounded-xl shadow-xl">
                    {{-- Previous Page Button --}}
                    @if ($paginator->onFirstPage())
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg text-neutral-600 bg-neutral-950 border border-neutral-800/80 cursor-default">
                            <i class="fa-solid fa-chevron-left text-xs"></i>
                        </span>
                    @else
                        <button type="button" wire:click="previousPage('{{ $paginator->getPageName() }}')" class="flex h-9 w-9 items-center justify-center rounded-lg text-neutral-300 hover:text-white bg-neutral-800 border border-neutral-700/80 transition hover:bg-neutral-700" title="Predchádzajúca stránka">
                            <i class="fa-solid fa-chevron-left text-xs"></i>
                        </button>
                    @endif

                    {{-- Page Numbers --}}
                    @if ($elements ?? null)
                        @foreach ($elements as $element)
                            @if (is_string($element))
                                <span class="flex h-9 min-w-9 px-2 items-center justify-center text-xs font-semibold text-neutral-500">
                                    {{ $element }}
                                </span>
                            @endif

                            @if (is_array($element))
                                @foreach ($element as $page => $url)
                                    <span wire:key="paginator-{{ $paginator->getPageName() }}-{{ $this->numberOfPaginatorsRendered[$paginator->getPageName()] }}-page{{ $page }}">
                                        @if ($page == $paginator->currentPage())
                                            <span class="flex h-9 min-w-9 px-3 items-center justify-center rounded-lg text-xs font-bold text-sky-300 bg-sky-500/20 border border-sky-500/40 shadow">
                                                {{ $page }}
                                            </span>
                                        @else
                                            <button type="button" wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" class="flex h-9 min-w-9 px-3 items-center justify-center rounded-lg text-xs font-semibold text-neutral-300 hover:text-white hover:bg-neutral-800 transition">
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
                        <button type="button" wire:click="nextPage('{{ $paginator->getPageName() }}')" class="flex h-9 w-9 items-center justify-center rounded-lg text-neutral-300 hover:text-white bg-neutral-800 border border-neutral-700/80 transition hover:bg-neutral-700" title="Nasledujúca stránka">
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </button>
                    @else
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg text-neutral-600 bg-neutral-950 border border-neutral-800/80 cursor-default">
                            <i class="fa-solid fa-chevron-right text-xs"></i>
                        </span>
                    @endif
                </div>
            </div>
        </nav>
    @endif
</div>
