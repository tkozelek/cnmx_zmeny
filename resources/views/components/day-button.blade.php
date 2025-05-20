@props(['id'])

<button type="submit" data-day="{{ $id }}"
    {{ $attributes->merge(['class' => 'rounded-t-xl add-user-btn w-full btn py-2 tracking-wider font-bold transition-all']) }}>
        {{ $slot }}
</button>
