<div x-data="{ openn: false }"
     @keydown.escape.window="openn = false"
     class="sticky top-0 z-40 w-full border-b border-neutral-800/80 bg-neutral-900/90 backdrop-blur-md shadow-sm py-1.5"
>
    <div class="container mx-auto px-4 md:px-6 lg:px-8 flex items-center justify-between">
        <!-- Logo and hamburger toggle -->
        <div class="flex items-center justify-between w-full md:w-auto py-1">
            <x-application-logo />

            <button
                type="button"
                @click.stop="openn = !openn"
                class="md:hidden relative z-[60] p-2 rounded-lg text-neutral-300 hover:text-white hover:bg-neutral-800 focus:outline-none transition"
                aria-label="Toggle menu"
            >
                <i class="fa-solid fa-bars text-xl" x-show="!openn"></i>
                <i class="fa-solid fa-xmark text-xl" x-show="openn"></i>
            </button>
        </div>

        <!-- Desktop Navbar -->
        @include('layouts.partials._desktop')
    </div>

    <!-- Mobile Drawer Overlay Teleported / High Z-Index -->
    <template x-teleport="body">
        @include('layouts.partials._mobile')
    </template>
</div>


