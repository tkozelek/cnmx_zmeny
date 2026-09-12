@aware(['isTailwind','isBootstrap','isBootstrap4','isBootstrap5', 'localisationPath'])
@if ($isTailwind)
    <button
        x-on:click.prevent="resetAllFilters"
        type="button"
        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-neutral-800 text-neutral-300 hover:bg-neutral-700 hover:text-white transition"
    >
        Zrušiť filtre
    </button>
@endif
