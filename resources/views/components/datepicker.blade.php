@props([
    'name' => null,
    'value' => null,
    'placeholder' => 'Vyberte dátum',
    'mode' => 'single',
])

<div
    x-data="{
        picker: null,
        init() {
            if (typeof window.flatpickr !== 'function') return;
            this.picker = window.flatpickr($refs.input, {
                mode: '{{ $mode }}',
                dateFormat: 'Y-m-d',
                altInput: true,
                altFormat: 'd.m.Y',
                defaultDate: '{{ $value }}' || null,
            });
        }
    }"
    class="relative inline-flex w-full items-center"
>
    <input
        x-ref="input"
        type="text"
        @if($name) name="{{ $name }}" @endif
        value="{{ $value }}"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'min-h-12 w-full rounded-xl border border-neutral-800 bg-neutral-900 px-4 py-3 text-sm font-medium text-neutral-100 placeholder-neutral-500 focus:border-neutral-700 focus:outline-none focus:ring-2 focus:ring-neutral-500 shadow-sm']) }}
    />
</div>
