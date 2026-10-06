<div class="w-full sm:w-auto">
    <div class="flex items-center gap-2">
        <label for="extra-note" class="whitespace-nowrap text-sm font-medium text-neutral-300">
            Poznámka:
        </label>

        <div class="relative flex-1 sm:flex-none">
            <input
                id="extra-note"
                type="text"
                wire:model.live.debounce.500ms="note"
                maxlength="{{ \App\Livewire\ExtraNote::MAX_LENGTH }}"
                placeholder="Napr. od 15:00"
                aria-describedby="extra-note-hint"
                @class([
                    'h-10 w-full sm:w-64 rounded-lg border bg-neutral-900 py-0 pl-3 text-base sm:text-sm text-neutral-100 placeholder-neutral-400 transition focus:border-neutral-500 focus:outline-none focus:ring-2 focus:ring-neutral-500/50',
                    'pr-9 border-neutral-500' => filled($note),
                    'pr-3 border-neutral-700' => blank($note),
                ])
            >

            @if(filled($note))
                <button
                    type="button"
                    wire:click="clear"
                    title="Vymazať poznámku"
                    aria-label="Vymazať poznámku"
                    class="absolute inset-y-0 right-0 flex w-9 items-center justify-center rounded-r-lg text-neutral-400 transition hover:text-rose-400"
                >
                    <i class="fa-solid fa-xmark" wire:loading.remove wire:target="clear" aria-hidden="true"></i>
                    <i class="fa-solid fa-spinner fa-spin text-xs" wire:loading wire:target="clear" aria-hidden="true"></i>
                </button>
            @endif
        </div>
    </div>

    <p id="extra-note-hint" class="mt-1 text-xs text-neutral-400 sm:text-right">
        Pridá sa ku dňom, na ktoré sa zapíšete odteraz.
    </p>
</div>
