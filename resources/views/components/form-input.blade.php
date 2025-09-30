@php
    $id = $id ?? $name;
@endphp

<div class="relative">
    <label for="{{ $id }}" class="block mb-2 text-sm font-medium text-gray-400">{{ $label }}</label>

    <div class="relative">
        {{-- Icon for the input field --}}
        @if($icon)
            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-gray-500">
                <i class="fa-solid {{ $icon }}"></i>
            </div>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $id }}"
            placeholder="{{ $placeholder }}"
            value="{{ $value }}"
            {{ $attributes->class([
                'w-full p-3 rounded-lg bg-gray-700 border border-gray-600 text-white placeholder-gray-500 transition duration-200',
                'focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500',
                'pl-10' => $icon,
                'pr-10' => $type === 'password'
            ]) }}
        >

        @if($type === 'password')
            <button
                type="button"
                class="absolute inset-y-0 right-0 flex items-center px-4 text-gray-500 hover:text-white transition duration-200"
                onclick="togglePasswordVisibility('{{ $id }}')"
            >
                <i id="{{ $id }}Icon" class="fa-solid fa-eye"></i>
            </button>
        @endif
    </div>

    @error($name)
        <p class="text-red-500 text-xs mt-1.5">{{ $message }}</p>
    @enderror
</div>
