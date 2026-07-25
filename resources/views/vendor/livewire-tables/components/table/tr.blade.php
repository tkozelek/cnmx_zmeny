@aware([ 'tableName','primaryKey','isTailwind','isBootstrap'])
@props(['row', 'rowIndex'])

@php
    $customAttributes = $this->getTrAttributes($row, $rowIndex);
@endphp

<tr
    rowpk='{{ $row->{$primaryKey} }}'
    x-on:dragstart.self="currentlyReorderingStatus && dragStart(event)"
    x-on:drop.prevent="currentlyReorderingStatus && dropEvent(event)"
    x-on:dragover.prevent.throttle.500ms="currentlyReorderingStatus && dragOverEvent(event)"
    x-on:dragleave.prevent.throttle.500ms="currentlyReorderingStatus && dragLeaveEvent(event)"
    @if($this->hasDisplayLoadingPlaceholder()) 
        wire:loading.class.add="hidden d-none"
    @else
        wire:loading.class.delay="opacity-50"
    @endif
    id="{{ $tableName }}-row-{{ $row->{$primaryKey} }}"
    :draggable="currentlyReorderingStatus"
    wire:key="{{ $tableName }}-tablerow-tr-{{ $row->{$primaryKey} }}"
    loopType="{{ ($rowIndex % 2 === 0) ? 'even' : 'odd' }}"
    {{
        $attributes->merge($customAttributes)
                ->class([
                    'bg-neutral-900 hover:bg-neutral-800/90 text-neutral-100 transition-colors duration-150 border-b border-neutral-800/50' => $isTailwind,
                ])
                ->except(['default','default-styling','default-colors'])
    }}
>
    {{ $slot }}
</tr>
