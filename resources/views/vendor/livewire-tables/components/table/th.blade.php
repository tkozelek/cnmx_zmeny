@aware(['isTailwind','isBootstrap'])
@props(['column', 'index'])

@php
    $allThAttributes = $this->getAllThAttributes($column);
    $customThAttributes = $allThAttributes['customAttributes'];
    $customSortButtonAttributes = $allThAttributes['sortButtonAttributes'];
    $customLabelAttributes = $allThAttributes['labelAttributes'];
    $customIconAttributes = $this->getThSortIconAttributes($column);
    $direction = $column->hasField() ? $this->getSort($column->getColumnSelectName()) : $this->getSort($column->getSlug()) ?? null;
@endphp

<th {{
    $attributes->merge($customThAttributes)
        ->class([
            'bg-neutral-950 text-neutral-200' => $isTailwind,
            'px-6 py-4 text-left text-xs font-bold whitespace-nowrap uppercase tracking-wider border-b border-neutral-800' => $isTailwind,
            'hidden' => $isTailwind && $column->shouldCollapseAlways(),
            'hidden md:table-cell' => $isTailwind && $column->shouldCollapseOnMobile(),
            'hidden lg:table-cell' => $isTailwind && $column->shouldCollapseOnTablet(),
        ])
        ->except(['default', 'default-colors', 'default-styling'])
}}>
    @if($column->getColumnLabelStatus())
        @unless ($this->sortingIsEnabled() && ($column->isSortable() || $column->getSortCallback()))
            <x-livewire-tables::table.th.label :$customLabelAttributes :columnTitle="$column->getTitle()" />
        @else
            @if ($isTailwind)
                <button wire:click="sortBy('{{ $column->getColumnSortKey() }}')" {{
                        $attributes->merge($customSortButtonAttributes)
                            ->class([
                                'text-neutral-200 hover:text-white font-bold' => $isTailwind,
                                'flex items-center space-x-1.5 text-left text-xs leading-4 uppercase tracking-wider group focus:outline-none' => $isTailwind,
                            ])
                            ->except(['default', 'default-colors', 'default-styling', 'wire:key'])
                }}>
                    <x-livewire-tables::table.th.label :$customLabelAttributes :columnTitle="$column->getTitle()" />
                    <x-livewire-tables::table.th.sort-icons :$direction :$customIconAttributes />
                </button>
            @endif
        @endunless
    @endif
</th>
