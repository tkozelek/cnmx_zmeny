@aware(['tableName','isTailwind','isBootstrap','isBootstrap4','isBootstrap5', 'localisationPath'])
@props(['filterKey', 'filterPillData'])

@php
    $filterButtonAttributes = $filterPillData->getCalculatedCustomResetButtonAttributes($filterKey,$this->getFilterPillsResetFilterButtonAttributes);
@endphp

@if ($isTailwind)
    <button
        class="flex-shrink-0 ml-1 h-4 w-4 rounded-full inline-flex items-center justify-center text-sky-400 hover:text-white focus:outline-none transition"
        title="Odstrániť filter"
        {{
            $attributes->merge($filterButtonAttributes)
            ->except(['default', 'default-colors', 'default-styling', 'default-text'])
        }}
    >
        <span class="sr-only">Odstrániť filter</span>
        <i class="fa-solid fa-xmark text-xs"></i>
    </button>
@endif
