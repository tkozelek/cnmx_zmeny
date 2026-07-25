<div>
    <x-livewire-tables::tools.filter-label :$filter :$filterLayout :$tableName :$isTailwind :$isBootstrap4 :$isBootstrap5 :$isBootstrap />
    <div @class([
        'rounded-lg shadow-sm' => $isTailwind,
        'mb-3 mb-md-0 input-group' => $isBootstrap,
    ])>
        <input type="date" {!! $filter->getWireMethod('filterComponents.'.$filter->getKey()) !!} {{
                $filterInputAttributes->merge()
                ->class([
                    'block w-full h-10 px-3 text-sm rounded-lg shadow-sm transition duration-150 ease-in-out focus:ring-0 focus:outline-none' => $isTailwind,
                    'border border-neutral-700 bg-neutral-800 text-neutral-100 focus:border-neutral-500' => $isTailwind,
                    'form-control' => $isBootstrap,
                ])
                ->except(['default-styling','default-colors']) 
            }} />
    </div>
</div>
