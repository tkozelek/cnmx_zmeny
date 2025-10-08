@props(['id', 'selected' => false])

<button type="submit" data-day="{{ $id }}" data-status="{{ $selected }}"
    {{ $attributes->merge(['class' => 'rounded-t-xl add-user-btn w-full btn py-1.5 tracking-wider font-extrabold text-lg transition-all text-gray-900']) }}>
        {{ $slot }}
</button>
