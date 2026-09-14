@aware(['tableName','isTailwind','isBootstrap4','isBootstrap5'])

<div x-data="filterPillsHandler(@js($setupData))" x-bind="trigger" 
    wire:key="{{ $tableName }}-filter-pill-{{ $filterKey }}"
    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-brand-500/10 text-brand-300 border border-brand-500/60"
>
    <span {{ $attributes->merge($pillTitleDisplayDataArray) }}></span>:&nbsp;
    <span {{ $attributes->merge($pillDisplayDataArray) }}></span>

    <x-livewire-tables::tools.filter-pills.buttons.reset-filter :$filterKey :$filterPillData/>
</div>
