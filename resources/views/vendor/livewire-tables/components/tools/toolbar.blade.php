@aware([ 'tableName','isTailwind','isBootstrap'])
@props([])
@php($toolBarAttributes = $this->getToolBarAttributesBag)

<div
    {{
        $toolBarAttributes->merge()
        ->class([
            'flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4' => $isTailwind && ($toolBarAttributes['default-styling'] ?? true),
            'd-md-flex justify-content-between mb-3' => $isBootstrap && ($toolBarAttributes['default-styling'] ?? true),
        ])
        ->except(['default','default-styling','default-colors'])
    }}
>
    <div @class([
            'd-md-flex' => $isBootstrap,
            'flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto' => $isTailwind,
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

        @if ($this->showSearchField())
            <div class="w-full sm:w-72 md:w-80">
                <x-livewire-tables::tools.toolbar.items.search-field />
            </div>
        @endif

        <div class="flex items-center justify-between sm:justify-start gap-3 w-full sm:w-auto">
            @if ($this->showFiltersButton())
                <x-livewire-tables::tools.toolbar.items.filter-button />
            @endif

            {{-- Mobile-only right container: per page aligns at the end of the row on mobile --}}
            <div class="flex items-center gap-2 sm:hidden ml-auto">
                @if ($this->columnSelectIsEnabled)
                    <x-livewire-tables::tools.toolbar.items.column-select />
                @endif

                @if ($this->showPaginationDropdown())
                    <x-livewire-tables::tools.toolbar.items.pagination-dropdown />
                @endif
            </div>
        </div>

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

    {{-- Desktop right items (sm and up) --}}
    <div x-cloak x-show="!currentlyReorderingStatus"
        @class([
            'd-md-flex' => $isBootstrap,
            'hidden sm:flex items-center justify-end gap-3 shrink-0 sm:ml-auto' => $isTailwind,
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

        @if ($this->showPaginationDropdown())
            <x-livewire-tables::tools.toolbar.items.pagination-dropdown />
        @endif

        @includeWhen($this->hasConfigurableAreaFor('toolbar-right-end'), $this->getConfigurableAreaFor('toolbar-right-end'), $this->getParametersForConfigurableArea('toolbar-right-end'))
    </div>
</div>

