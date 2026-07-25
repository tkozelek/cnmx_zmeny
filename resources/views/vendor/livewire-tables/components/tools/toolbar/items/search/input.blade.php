@aware(['isTailwind', 'isBootstrap'])
<input
    wire:model{{ $this->getSearchOptions() }}="search"
    placeholder="{{ $this->getSearchPlaceholder() }}"
    type="text"
    {{ 
        $attributes->merge($this->getSearchFieldAttributes())
        ->class([
            'rounded-lg shadow-sm transition duration-150 ease-in-out text-sm rounded-lg focus:ring-0 focus:border-neutral-500' => $isTailwind,
            'border-neutral-700 bg-neutral-800 text-neutral-100 placeholder-neutral-400 focus:border-neutral-500' => $isTailwind,
            'block w-full' => !$this->hasSearchIcon,
            'pl-9 pr-4 py-2' => $this->hasSearchIcon,
            'form-control' => $isBootstrap && $this->getSearchFieldAttributes()['default'] ?? true,
        ])
        ->except(['default','default-styling','default-colors']) 
    }}
/>