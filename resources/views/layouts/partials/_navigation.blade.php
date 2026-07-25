<div class="sticky top-0 z-50 w-full border-b border-neutral-800/80 bg-neutral-900/90 backdrop-blur-md shadow-sm py-1.5">
    <div x-data="{ openn: false }" @keydown.escape.window="openn = false"
         class="container mx-auto px-4 md:px-6 lg:px-8 flex flex-col md:flex-row md:items-center md:justify-between">
        <!-- Logo and burger button -->
        <div class="flex flex-row items-center justify-between py-1 z-50">
            <x-application-logo/>
            <button class="md:hidden p-2 rounded-lg text-neutral-400 hover:text-white hover:bg-neutral-800 focus:outline-none" @click="openn = !openn"
                    aria-label="Toggle menu">
                <svg fill="currentColor" viewBox="0 0 20 20" class="w-6 h-6">
                    <path x-show="!openn" fill-rule="evenodd"
                          d="M3 5a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zM3 15a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                          clip-rule="evenodd"></path>
                    <path x-show="openn" fill-rule="evenodd"
                          d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                          clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>

        <!-- Mobile Drawer -->
        @include('layouts.partials._mobile')

        <!-- Desktop Navbar -->
        @include('layouts.partials._desktop')
    </div>
</div>
