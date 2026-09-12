<div>
    <x-livewire-tables::tools.filter-label :$filter :$filterLayout :$tableName :$isTailwind :$isBootstrap4 :$isBootstrap5 :$isBootstrap />

    @if ($isTailwind)
    <div class="rounded-lg shadow-sm">
    @endif
        <select multiple
            {!! $filter->getWireMethod('filterComponents.'.$filter->getKey()) !!} {{
                $filterInputAttributes->merge([
                    'wire:key' => $filter->generateWireKey($tableName, 'multiselectdropdown'),
                ])
                ->class([
                    'block w-full transition duration-150 ease-in-out rounded-lg shadow-sm focus:ring-0 focus:outline-none' => $isTailwind,
                    'border-neutral-700 bg-neutral-800 text-neutral-100 focus:border-neutral-500' => $isTailwind,
                    'form-control' => $isBootstrap4 && ($filterInputAttributes['default-styling'] ?? true),
                    'form-select' => $isBootstrap5 && ($filterInputAttributes['default-styling'] ?? true),
                ])
                ->except(['default-styling','default-colors']) 
            }}>
        @if ($filter->getFirstOption() !== '')
            <option @if($filter->isEmpty($this)) selected @endif value="all" class="bg-neutral-900 text-neutral-100">{{ $filter->getFirstOption()}}</option>
        @endif
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
    @if ($isTailwind)
    </div>
    @endif
</div>
