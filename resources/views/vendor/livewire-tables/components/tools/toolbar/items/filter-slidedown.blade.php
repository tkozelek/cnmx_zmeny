@aware([ 'tableName', 'isTailwind', 'isBootstrap'])
@props([])

<div x-cloak x-show="filtersOpen" {{ $attributes
            ->merge($this->getFilterSlidedownWrapperAttributes)
            ->merge($isTailwind ? [
                'x-transition:enter' => 'transition ease-out duration-100',
                'x-transition:enter-start' => 'transform opacity-0',
                'x-transition:enter-end' => 'transform opacity-100',
                'x-transition:leave' => 'transition ease-in duration-75',
                'x-transition:leave-start' => 'transform opacity-100',
                'x-transition:leave-end' => 'transform opacity-0',
            ] : [])
            ->class([
                'container' => $isBootstrap && ($this->getFilterSlidedownWrapperAttributes['default'] ?? true),
            ])
            ->except(['default','default-colors','default-styling'])
        }} 

>
    @foreach ($this->getFiltersByRow() as $filterRowIndex => $filtersInRow)
        @php($defaultAttributes = $this->getFilterSlidedownRowAttributes($filterRowIndex))
        <div {{ $attributes
            ->merge($defaultAttributes)
            ->merge([
                'row' => $filterRowIndex,
            ])
            ->class([
                'row col-12' => $isBootstrap && ($defaultAttributes['default-styling'] ?? true),
                'grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 p-4 mb-4 rounded-xl border border-neutral-800 bg-neutral-900/60 shadow-lg' => $isTailwind && ($defaultAttributes['default-styling'] ?? true),
            ])
            ->except(['default','default-colors','default-styling'])
        }} 
        >
            @foreach ($filtersInRow as $filter)
                <div
                    @class([
                        'space-y-1 mb-4' =>
                            $isBootstrap,
                        'col-12 col-sm-9 col-md-6 col-lg-3' =>
                            $isBootstrap &&
                            !$filter->hasFilterSlidedownColspan(),
                        'col-12 col-sm-6 col-md-6 col-lg-3' =>
                            $isBootstrap &&
                            $filter->hasFilterSlidedownColspan() &&
                            $filter->getFilterSlidedownColspan() === 2,
                        'col-12 col-sm-3 col-md-3 col-lg-3' =>
                            $isBootstrap &&
                            $filter->hasFilterSlidedownColspan() &&
                            $filter->getFilterSlidedownColspan() === 3,
                        'col-12 col-sm-1 col-md-1 col-lg-1' =>
                            $isBootstrap &&
                            $filter->hasFilterSlidedownColspan() &&
                            $filter->getFilterSlidedownColspan() === 4,
                        'space-y-1' =>
                            $isTailwind,
                    ])
                    id="{{ $tableName }}-filter-{{ $filter->getKey() }}-wrapper"
                >
                    {{ $filter->setGenericDisplayData($this->getFilterGenericData)->render() }}
                </div>
            @endforeach
        </div>
    @endforeach
</div>
