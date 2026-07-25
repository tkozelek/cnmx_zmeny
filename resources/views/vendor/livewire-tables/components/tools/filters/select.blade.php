<div>
    <x-livewire-tables::tools.filter-label :$filter :$filterLayout :$tableName :$isTailwind :$isBootstrap4 :$isBootstrap5 :$isBootstrap />

    <div @class([
        'rounded-lg shadow-sm' => $isTailwind,
        'inline' => $isBootstrap,
    ])>
        <select {!! $filter->getWireMethod('filterComponents.'.$filter->getKey()) !!} {{
                $filterInputAttributes->merge()
                ->class([
                    'block w-full h-10 px-3 text-sm transition duration-150 ease-in-out rounded-lg shadow-sm focus:ring-0 focus:outline-none' => $isTailwind,
                    'border border-neutral-700 bg-neutral-800 text-neutral-100 focus:border-neutral-500' => $isTailwind,
                    'form-control' => $isBootstrap4 && ($filterInputAttributes['default-styling'] ?? true),
                    'form-select' => $isBootstrap5 && ($filterInputAttributes['default-styling'] ?? true),
                ])
                ->except(['default-styling','default-colors'])
            }}>
            @foreach($filter->getOptions() as $key => $value)
                @if (is_iterable($value))
                    <optgroup label="{{ $key }}">
                        @foreach ($value as $optionKey => $optionValue)
                            <option value="{{ $optionKey }}" class="bg-neutral-900 text-neutral-100">{{ $optionValue }}</option>
                        @endforeach
                    </optgroup>
                @else
                    <option value="{{ $key }}" class="bg-neutral-900 text-neutral-100">{{ $value }}</option>
                @endif
            @endforeach
        </select>
    </div>
</div>
