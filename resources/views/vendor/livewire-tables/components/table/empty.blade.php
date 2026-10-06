@aware(['isTailwind','isBootstrap'])

@php($attributes = $attributes->merge(['wire:key' => 'empty-message-'.$this->getId()]))

@if ($isTailwind)
    <tr {{ $attributes }}>
        <td colspan="{{ $this->getColspanCount() }}">
            <div class="sticky left-0 bg-neutral-900 px-4 py-8 text-center sm:static">
                <span class="font-medium text-neutral-400 text-base">{{ $this->getEmptyMessage() }}</span>
            </div>
        </td>
    </tr>
@elseif ($isBootstrap)
     <tr {{ $attributes }}>
        <td colspan="{{ $this->getColspanCount() }}">
            {{ $this->getEmptyMessage() }}
        </td>
    </tr>
@endif
