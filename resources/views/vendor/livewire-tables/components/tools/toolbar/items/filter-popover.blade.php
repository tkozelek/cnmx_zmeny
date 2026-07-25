@aware(['tableName'])
<div
    x-cloak
    x-show="filterPopoverOpen"
    x-transition:enter="transition ease-out duration-100"
    x-transition:enter-start="transform opacity-0 scale-95"
    x-transition:enter-end="transform opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-75"
    x-transition:leave-start="transform opacity-100 scale-100"
    x-transition:leave-end="transform opacity-0 scale-95"
    class="origin-top-left absolute left-0 mt-2 w-full md:w-64 rounded-xl border border-neutral-800 bg-neutral-900 p-3 shadow-2xl z-50 focus:outline-none text-neutral-100"
>
    @foreach ($this->getVisibleFilters() as $filter)
        <div id="{{ $tableName }}-filter-{{ $filter->getKey() }}-wrapper" wire:key="{{ $tableName }}-filter-{{ $filter->getKey() }}-toolbar" class="py-2 text-sm text-neutral-200">
            {{ $filter->setGenericDisplayData($this->getFilterGenericData)->render() }}
        </div>
    @endforeach

    @if ($this->hasAppliedVisibleFiltersWithValuesThatCanBeCleared())
        <div class="pt-2 border-t border-neutral-800 mt-2">
            <x-livewire-tables::tools.toolbar.items.filter-popover.clear-button />
        </div>
    @endif
</div>