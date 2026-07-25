@aware(['tableName','isTailwind','isBootstrap4','isBootstrap5'])
@props([
    'filterKey', 
    'filterPillData', 
    'shouldWatch' => ($filterPillData->shouldWatchForEvents() ?? 0),
    'filterPillsItemAttributes' => $filterPillData->getFilterPillsItemAttributes(),
])

<div x-data="filterPillsHandler(@js($filterPillData->getPillSetupData($filterKey,$shouldWatch)))" x-bind="trigger" 
    wire:key="{{ $tableName }}-filter-pill-{{ $filterKey }}" 
    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-sky-500/10 text-sky-300 border border-sky-500/30"
>
    <span x-text="localFilterTitle + ':&nbsp;'"></span>
    <span {{ $filterPillData->getFilterPillDisplayData() }}></span>
    <x-livewire-tables::tools.filter-pills.buttons.reset-filter :$filterKey :$filterPillData/>
</div>
