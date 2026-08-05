<div>
    @if($this->enabled)
        <button type="button" wire:click="suggest" wire:loading.attr="disabled" wire:target="suggest"
                title="Navrhne zaradenia pre všetkých sedem dní naraz - nič sa neuloží, kým návrh nepotvrdíte"
                class="inline-flex h-11 items-center gap-2 rounded-xl border border-violet-500/40 bg-violet-500/10 px-4 text-sm font-semibold text-violet-300 transition hover:bg-violet-500/20 disabled:opacity-50">
            <span wire:loading.remove wire:target="suggest" class="flex items-center gap-2">
                <i class="fa-solid fa-wand-magic-sparkles text-xs"></i>
                AI navrhni celý týždeň
            </span>
            <span wire:loading wire:target="suggest" class="flex items-center gap-2">
                <i class="fa-solid fa-spinner fa-spin text-xs"></i>
                Navrhujem…
            </span>
        </button>
    @endif
</div>
