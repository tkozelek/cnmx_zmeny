@props(['title', 'subtitle', 'wide' => false])

<div class="relative isolate flex w-full flex-1 justify-center bg-neutral-950 px-4 py-10 sm:items-center sm:py-16">
    <div class="auth-backdrop" aria-hidden="true"></div>

    <div @class(['w-full', 'max-w-md' => ! $wide, 'max-w-lg' => $wide])>
        <div class="mb-6 text-center sm:mb-8">
            <h1 class="text-2xl font-semibold tracking-tight text-white sm:text-3xl">{{ $title }}</h1>
            <p class="mt-2 text-sm text-neutral-400 sm:text-base">{{ $subtitle }}</p>
        </div>

        <div class="rounded-xl border border-neutral-800 bg-neutral-900 p-5 sm:p-8">
            {{ $slot }}

            <div class="mt-8 border-t border-neutral-800 pt-4 text-center text-sm text-neutral-400">
                {{ $footer }}
            </div>
        </div>
    </div>
</div>
