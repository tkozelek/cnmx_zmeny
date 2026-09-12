@aware(['isTailwind', 'isBootstrap'])

<div class="relative flex items-center w-full">
    <div class="absolute inset-y-0 start-0 flex items-center ps-3.5 pointer-events-none text-neutral-400">
        <i class="fa-solid fa-magnifying-glass text-xs"></i>
    </div>

    <input
        wire:model{{ $this->getSearchOptions() }}="search"
        placeholder="{{ $this->getSearchPlaceholder() ?: 'Vyhľadať v tabuľke...' }}"
        type="text"
        class="block w-full h-10 rounded-lg border border-neutral-700 bg-neutral-800 ps-10 pe-9 text-sm text-neutral-100 placeholder-neutral-400 transition focus:border-neutral-500 focus:outline-none"
    />

    @if ($this->hasSearch)
        <button
            type="button"
            wire:click="clearSearch"
            class="absolute inset-y-0 end-0 flex items-center pe-3 text-neutral-400 hover:text-white"
            title="Vymazať vyhľadávanie"
        >
            <i class="fa-solid fa-xmark text-xs"></i>
        </button>
    @endif
</div>
