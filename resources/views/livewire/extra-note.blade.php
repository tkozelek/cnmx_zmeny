<div class="flex items-center gap-2">
    <label for="extra-note" class="whitespace-nowrap text-sm font-medium text-neutral-300">
        Extra info:
    </label>

    <div class="relative">
        <input
            id="extra-note"
            type="text"
            wire:model.live.debounce.300ms="note"
            maxlength="{{ \App\Livewire\ExtraNote::MAX_LENGTH }}"
            placeholder="Napr. od 15:00"
            @class([
                'h-10 w-48 sm:w-64 rounded-lg border bg-neutral-900 py-0 pl-3 text-sm text-neutral-100 placeholder-neutral-500 transition focus:border-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-500/50 shadow-sm',
                'pr-9 border-neutral-500' => filled($note),
                'pr-3 border-neutral-800' => blank($note),
            ])
        >

        @if(filled($note))
            <button
                type="button"
                wire:click="clear"
                title="Vymazať extra info"
                aria-label="Vymazať extra info"
                class="absolute inset-y-0 right-0 flex w-9 items-center justify-center rounded-r-lg text-neutral-400 transition hover:text-rose-400 focus:outline-none"
            >
                <i class="fa-solid fa-xmark" wire:loading.remove wire:target="clear"></i>
                <i class="fa-solid fa-spinner fa-spin text-xs" wire:loading wire:target="clear"></i>
            </button>
        @endif
    </div>
</div>
