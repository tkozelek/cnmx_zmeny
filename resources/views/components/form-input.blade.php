@php
    $id = $id ?? $name;
    $hasError = $errors->has($name);
@endphp

<div class="relative">
    <label for="{{ $id }}" class="block mb-2 text-sm font-medium text-neutral-300">{{ $label }}</label>

    <div class="relative">
        {{-- Icon for the input field --}}
        @if($icon)
            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-neutral-400">
                <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
            </div>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $id }}"
            placeholder="{{ $placeholder }}"
            value="{{ $value }}"
            @if($hasError) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->class([
                'w-full px-4 py-3 rounded-xl bg-neutral-900 border text-white placeholder-neutral-400 transition-all duration-200 text-base sm:text-sm disabled:opacity-60 disabled:cursor-not-allowed',
                'border-rose-500/70' => $hasError,
                'border-neutral-700' => ! $hasError,
                'focus:outline-none focus:ring-2 focus:ring-brand-500/60 focus:border-brand-500 hover:border-neutral-600',
                'pl-11' => $icon,
                'pr-11' => $type === 'password'
            ]) }}
        >


        @if($type === 'password')
            <button
                type="button"
                class="absolute inset-y-0 right-0 flex items-center px-4 text-neutral-400 hover:text-neutral-300 transition duration-200"
                onclick="togglePasswordVisibility('{{ $id }}')"
                aria-label="Zobraziť alebo skryť heslo"
            >
                <i id="{{ $id }}Icon" class="fa-solid fa-eye" aria-hidden="true"></i>
            </button>
        @endif
    </div>

    @error($name)
        <p id="{{ $id }}-error" class="text-rose-400 text-xs mt-1.5 flex items-center gap-1 font-medium"><i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i> {{ $message }}</p>
    @enderror
</div>



