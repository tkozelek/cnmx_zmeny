@php
    $id = $id ?? $name;
@endphp

<div class="relative">
    <label for="{{ $id }}" class="block mb-2 text-xs font-semibold uppercase tracking-wider text-neutral-400">{{ $label }}</label>

    <div class="relative">
        {{-- Icon for the input field --}}
        @if($icon)
            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-neutral-500">
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
                'w-full px-4 py-3 rounded-xl bg-neutral-900 border border-neutral-800 text-white placeholder-neutral-500 transition-all duration-200 text-sm shadow-inner disabled:opacity-60 disabled:cursor-not-allowed',
                'focus:outline-none focus:ring-2 focus:ring-sky-500/60 focus:border-sky-500 hover:border-neutral-700',
                'pl-11' => $icon,
                'pr-11' => $type === 'password'
            ]) }}
        >


        @if($type === 'password')
            <button
                type="button"
                class="absolute inset-y-0 right-0 flex items-center px-4 text-neutral-500 hover:text-neutral-300 transition duration-200"
                onclick="togglePasswordVisibility('{{ $id }}')"
            >
                <i id="{{ $id }}Icon" class="fa-solid fa-eye"></i>
            </button>
        @endif
    </div>

    @error($name)
        <p class="text-rose-400 text-xs mt-1.5 flex items-center gap-1 font-medium"><i class="fa-solid fa-circle-exclamation"></i> {{ $message }}</p>
    @enderror
</div>



