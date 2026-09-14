@aware([ 'tableName','isTailwind','isBootstrap','isBootstrap4','isBootstrap5', 'localisationPath'])
@props([])

<div class="relative block md:inline-block text-left">
    <div
        @if ($this->isFilterLayoutPopover())
            x-data="{ filterPopoverOpen: false }"
            x-on:keydown.escape.stop="if (!this.childElementOpen) { filterPopoverOpen = false }"
            x-on:mousedown.away="if (!this.childElementOpen) { filterPopoverOpen = false }"
        @endif
        class="relative block md:inline-block text-left"
    >
        <div>
            <button
                type="button"
                class="inline-flex items-center justify-center gap-2 h-10 rounded-lg border border-neutral-700 shadow-sm px-4 bg-neutral-800 text-sm font-semibold text-neutral-100 hover:bg-neutral-700 transition focus:outline-none"
                @if ($this->isFilterLayoutPopover())
                    x-on:click="filterPopoverOpen = !filterPopoverOpen"
                    aria-haspopup="true"
                    x-bind:aria-expanded="filterPopoverOpen"
                    aria-expanded="true"
                @endif
                @if ($this->isFilterLayoutSlideDown()) x-on:click="filtersOpen = !filtersOpen" @endif
            >
                <i wire:loading.remove wire:target="filterComponents,setFilter,clearFilter" class="fa-solid fa-filter text-xs text-brand-400"></i>
                <i wire:loading wire:target="filterComponents,setFilter,clearFilter" class="fa-solid fa-circle-notch fa-spin text-xs text-brand-400"></i>
                <span>Filtre</span>

                @if ($count = $this->getFilterBadgeCount())
                    <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-brand-500/20 text-brand-300 border border-brand-500/60">
                        {{ $count }}
                    </span>
                @endif

                <i class="fa-solid fa-chevron-down text-xs text-neutral-400 ml-1"></i>
            </button>
        </div>

        @if ($this->isFilterLayoutPopover())
            <x-livewire-tables::tools.toolbar.items.filter-popover  />
        @endif
    </div>
</div>
