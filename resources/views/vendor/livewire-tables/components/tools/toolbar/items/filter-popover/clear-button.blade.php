@aware(['isTailwind','isBootstrap4','isBootstrap5', 'localisationPath'])
@if ($isTailwind)
    <button type="button" wire:click.prevent="setFilterDefaults" x-on:click="filterPopoverOpen = false"
        class="w-full inline-flex items-center justify-center px-3 py-2 border border-neutral-700 shadow-sm text-sm leading-4 font-semibold rounded-lg text-neutral-200 bg-neutral-800 hover:bg-neutral-700 transition focus:outline-none"
    >
        Zrušiť filtre
    </button>
@endif