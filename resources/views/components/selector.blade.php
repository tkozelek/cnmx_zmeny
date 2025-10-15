@props([
    'name',
    'label',
    'items',
    'placeholder' => 'Vyhľadaj...',
    'itemValue' => 'id',
    'itemText' => 'name',
    'redirect' => '',
    'width' => 'full'
])

@php
$width = [
    'full' => 'w-full',
    '1/3' => 'w-1/3',
][$width];
@endphp

<div x-data="searchableSelect({
        items: {{ Js::from($items) }},
        oldValue: '{{ old($name) }}',
        itemValue: '{{ $itemValue }}',
        itemText: '{{ $itemText }}',
        redirect: '{{ $redirect }}'
    })"
     x-init="init()"
     class="relative {{ $width }}"
>
    <label for="{{ $name }}_search" class="block mb-2 text-sm font-medium text-white">{{ $label }}</label>

    <div class="relative">
        <input
            type="text"
            id="{{ $name }}_search"
            x-model="search"
            @focus="open = true"
            @click.away="open = false"
            @keydown.escape.prevent="open = false; $el.blur()"
            @keydown.arrow-down.prevent="focusNext()"
            @keydown.arrow-up.prevent="focusPrev()"
            @keydown.enter.prevent="selectFocused(); open = false;"
            placeholder="{{ $placeholder }}"
            class="border sm:text-sm rounded-lg block w-full p-2.5 bg-gray-800 border-gray-600 placeholder-gray-400 text-white focus:ring-blue-500 focus:border-blue-500"
            autocomplete="off"
        >
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <!-- Search Icon -->
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
        </div>
    </div>

    <input type="hidden" name="{{ $name }}" x-model="selectedValue">

    {{-- Dropdown Panel --}}
    <div x-show="open"
         x-transition
         class="absolute z-10 w-full mt-1 bg-gray-700 border border-gray-600 rounded-md shadow-lg max-h-60 overflow-auto"
         style="display: none;"
    >
        <template x-for="(item, index) in filteredItems" :key="item[itemValue]">
            <div @click="selectItem(item)"
                 :class="{ 'bg-gray-600': focusedIndex === index }"
                 @mouseenter="focusedIndex = index"
                 class="px-4 py-2 text-sm text-white hover:bg-gray-600 cursor-pointer"
                 :data-value="item[itemValue]"
                 x-text="item[itemText] + ' ' + item['shifts_count']"
            ></div>
        </template>
        <template x-if="filteredItems.length === 0">
            <div class="px-4 py-2 text-sm text-gray-400">Nenašli sa žiadne položky.</div>
        </template>
    </div>
</div>

<script>
    function searchableSelect(config) {
        return {
            open: false,
            search: '',
            items: config.items || [],
            itemValue: config.itemValue || 'id',
            itemText: config.itemText || 'name',
            selectedValue: null,
            focusedIndex: -1,
            redirect: config.redirect || '',

            init() {
                if (config.oldValue) {
                    const oldItem = this.items.find(i => i[this.itemValue] == config.oldValue);
                    if (oldItem) {
                        this.selectItem(oldItem, false);
                    }
                }
            },

            get filteredItems() {
                this.focusedIndex = -1;
                if (this.search === '') {
                    return this.items;
                }
                return this.items.filter(
                    item => String(item[this.itemText])
                        .toLowerCase()
                        .normalize("NFD")
                        .replace(/[\u0300-\u036f]/g, "")
                        .includes(this.search.toLowerCase()
                            .normalize("NFD")
                            .replace(/[\u0300-\u036f]/g, ""))
                );
            },

            selectItem(item, closeDropdown = true) {
                this.selectedValue = item[this.itemValue];
                this.search = item[this.itemText];
                if (closeDropdown) {
                    this.open = false;
                }
                this.focusedIndex = -1;

                if (this.redirect) {
                    window.location.href = this.redirect + '/' + this.selectedValue;
                }
            },

            focusNext() {
                if(this.focusedIndex < this.filteredItems.length - 1) {
                    this.focusedIndex++;
                }
            },

            focusPrev() {
                if(this.focusedIndex > 0) {
                    this.focusedIndex--;
                }
            },

            selectFocused() {
                if(this.focusedIndex > -1) {
                    this.selectItem(this.filteredItems[this.focusedIndex]);
                }
            }
        }
    }
</script>
