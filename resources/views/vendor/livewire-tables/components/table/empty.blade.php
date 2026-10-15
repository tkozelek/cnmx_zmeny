@aware(['isTailwind','isBootstrap'])

@php($attributes = $attributes->merge(['wire:key' => 'empty-message-'.$this->getId()]))

@if ($isTailwind)
    <tr {{ $attributes }}>
        <td colspan="{{ $this->getColspanCount() }}">
            {{-- As wide as the visible part of the scroller on phones, so the message isn't centred off-screen. --}}
            <div class="sticky left-0 w-[min(100%,calc(100vw-2.5rem))] bg-neutral-900 px-4 py-8 text-center sm:static sm:w-auto">
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
