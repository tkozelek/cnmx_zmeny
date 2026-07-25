@aware([ 'tableName','isTailwind','isBootstrap','isBootstrap4','isBootstrap5', 'localisationPath'])
<div class="ml-0 md:ml-2">
    <select
        wire:model.live="perPage"
        id="{{ $tableName }}-perPage"
        class="block h-10 rounded-lg border border-neutral-700 bg-neutral-800 px-3 text-sm font-semibold text-neutral-100 transition focus:border-neutral-500 focus:outline-none cursor-pointer"
    >
        @foreach ($this->getPerPageAccepted() as $item)
            <option
                value="{{ $item }}"
                wire:key="{{ $tableName }}-per-page-{{ $item }}"
                class="bg-neutral-900 text-neutral-100"
            >
                {{ $item === -1 ? 'Všetky' : $item.' na stranu' }}
            </option>
        @endforeach
    </select>
</div>
