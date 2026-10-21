<div class="field">
    <label for="{{ $inputId }}" class="field__label text-sm font-medium text-neutral-200">{{ $label }}</label>

    @if($hint)
        <p id="{{ $inputId }}-hint" class="field__hint text-sm text-neutral-400">{{ $hint }}</p>
    @endif

    <div class="field__control relative">
        @if($icon)
            <div class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-neutral-400">
                <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
            </div>
        @endif

        <input
            type="{{ $type }}"
            name="{{ $name }}"
            id="{{ $inputId }}"
            placeholder="{{ $placeholder }}"
            value="{{ $value }}"
            @if($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if($error) aria-invalid="true" @endif
            {{ $attributes->class([
                'w-full min-h-12 px-4 py-3 rounded-xl bg-neutral-900 border text-white placeholder-neutral-400 transition-all duration-200 text-base sm:text-sm disabled:opacity-60 disabled:cursor-not-allowed',
                'border-rose-500/70' => $error,
                'border-neutral-500' => ! $error,
                'focus:outline-none focus:ring-2 focus:ring-brand-500/60 focus:border-brand-500 hover:border-neutral-400',
                'pl-11' => $icon,
                'pr-12' => $type === 'password',
            ]) }}
        >

        @if($type === 'password')
            <button
                type="button"
                class="absolute inset-y-0 right-0 flex w-12 items-center justify-center rounded-r-xl text-neutral-400 hover:text-neutral-200 transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-400"
                onclick="togglePasswordVisibility('{{ $inputId }}', this)"
                aria-label="Zobraziť heslo"
                aria-pressed="false"
            >
                <i id="{{ $inputId }}Icon" class="fa-solid fa-eye" aria-hidden="true"></i>
            </button>
        @endif
    </div>

    @if($error)
        <p id="{{ $inputId }}-error" class="field__error flex items-start gap-1.5 text-sm font-medium text-rose-400">
            <i class="fa-solid fa-circle-exclamation mt-0.5" aria-hidden="true"></i>
            <span>{{ $error }}</span>
        </p>
    @endif
</div>
