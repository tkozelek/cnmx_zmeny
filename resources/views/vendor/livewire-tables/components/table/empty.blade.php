@aware(['isTailwind','isBootstrap'])

@php($attributes = $attributes->merge(['wire:key' => 'empty-message-'.$this->getId()]))

@if ($isTailwind)
    <tr {{ $attributes }}>
        <td colspan="{{ $this->getColspanCount() }}">
            <div class="flex justify-center items-center space-x-2 bg-neutral-900">
                <span class="font-medium py-8 text-neutral-400 text-lg">Žiadne výsledky neboli nájdené.</span>
            </div>
        </td>
    </tr>
@elseif ($isBootstrap)
     <tr {{ $attributes }}>
        <td colspan="{{ $this->getColspanCount() }}">
            Žiadne výsledky neboli nájdené.
        </td>
    </tr>
@endif
