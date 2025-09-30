<div class="w-full text-gray-200 bg-slate-900 shadow-md py-1">
    <div x-data="{ openn: false }" @keydown.escape.window="openn = false" class="flex flex-col container px-4 mx-auto md:items-center md:justify-between md:flex-row">
        <!-- Logo and burger -->
        <div class="p-2 flex flex-row items-center justify-between z-50">
            <x-application-logo/>
            <button class="md:hidden rounded-lg focus:outline-none focus:shadow-outline" @click="openn = !openn" aria-label="Toggle menu">
                <svg fill="currentColor" viewBox="0 0 20 20" class="w-6 h-6">
                    <path x-show="!openn" fill-rule="evenodd" d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"></path>
                    <path x-show="openn" fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>

        <!-- Mobile -->
        @include('layouts.partials._mobile')

        <!-- Desktop -->
        @include('layouts.partials._desktop')
    </div>
</div>
