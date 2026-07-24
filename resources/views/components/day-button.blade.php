@props(['id', 'selected' => false])

<button type="submit" data-day="{{ $id }}" data-status="{{ $selected }}"
    {{ $attributes->merge(['class' => 'add-user-btn w-full py-1.5 text-lg font-extrabold tracking-wider transition-colors disabled:cursor-not-allowed']) }}>
        {{ $slot }}
</button>
