@aware([ 'tableName','isTailwind','isBootstrap'])
@props([])
@php($toolBarAttributes = $this->getToolBarAttributesBag)

<div
    {{
        $toolBarAttributes->merge()
        ->class([
            'flex flex-wrap items-center gap-2 md:justify-between mb-4 px-4 md:p-0' => $isTailwind && ($toolBarAttributes['default-styling'] ?? true),
            'd-md-flex justify-content-between mb-3' => $isBootstrap && ($toolBarAttributes['default-styling'] ?? true),
        ])
        ->except(['default','default-styling','default-colors'])
    }}
>
    <div @class([
            'd-md-flex' => $isBootstrap,
            'flex flex-1 flex-wrap items-center gap-2 min-w-0 md:w-2/4 md:flex-none' => $isTailwind,
        ])
    >
        @if ($this->hasConfigurableAreaFor('toolbar-left-start'))
            <div x-cloak x-show="!currentlyReorderingStatus" @class([
                'mb-3 mb-md-0 input-group' => $isBootstrap,
                'flex rounded-md shadow-sm' => $isTailwind,
            ])>
                @include($this->getConfigurableAreaFor('toolbar-left-start'), $this->getParametersForConfigurableArea('toolbar-left-start'))
            </div>
        @endif

        @if ($this->showReorderButton())
            <x-livewire-tables::tools.toolbar.items.reorder-buttons />
        @endif

        {{-- Takes whatever width the buttons beside it leave, down to a still-usable minimum. --}}
        @if ($this->showSearchField())
            <div class="flex-1 min-w-[9rem]">
                <x-livewire-tables::tools.toolbar.items.search-field />
            </div>
        @endif

        @if ($this->showFiltersButton())
            <x-livewire-tables::tools.toolbar.items.filter-button />
        @endif

        @if($this->showActionsInToolbarLeft())
            <x-livewire-tables::includes.actions/>
        @endif

        @if ($this->hasConfigurableAreaFor('toolbar-left-end'))
            <div x-cloak x-show="!currentlyReorderingStatus" @class([
                'mb-3 mb-md-0 input-group' => $isBootstrap,
                'flex rounded-md shadow-sm' => $isTailwind,
            ])>
                @include($this->getConfigurableAreaFor('toolbar-left-end'), $this->getParametersForConfigurableArea('toolbar-left-end'))
            </div>
        @endif
    </div>

    <div x-cloak x-show="!currentlyReorderingStatus"
        @class([
            'd-md-flex' => $isBootstrap,
            'flex flex-wrap items-center gap-2 shrink-0' => $isTailwind,
        ])
    >
        @includeWhen($this->hasConfigurableAreaFor('toolbar-right-start'), $this->getConfigurableAreaFor('toolbar-right-start'), $this->getParametersForConfigurableArea('toolbar-right-start'))

        @if($this->showActionsInToolbarRight())
            <x-livewire-tables::includes.actions/>
        @endif

        @if ($this->showBulkActionsDropdownAlpine() && $this->shouldAlwaysHideBulkActionsDropdownOption != true)
            <x-livewire-tables::tools.toolbar.items.bulk-actions />
        @endif

        @if ($this->columnSelectIsEnabled)
            <x-livewire-tables::tools.toolbar.items.column-select />
        @endif

        {{-- The select itself is `block w-full`; the cap is put on its wrapper so it sizes to the
             numbers it holds instead of eating the row. --}}
        @if ($this->showPaginationDropdown())
            <div class="w-[5.5rem]">
                <x-livewire-tables::tools.toolbar.items.pagination-dropdown />
            </div>
        @endif

        @includeWhen($this->hasConfigurableAreaFor('toolbar-right-end'), $this->getConfigurableAreaFor('toolbar-right-end'), $this->getParametersForConfigurableArea('toolbar-right-end'))
    </div>
</div>

