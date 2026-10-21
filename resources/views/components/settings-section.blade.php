@props(['id', 'title', 'description'])

<section id="{{ $id }}" aria-labelledby="{{ $id }}-title" class="flex flex-col gap-5 rounded-lg border border-neutral-800 bg-neutral-900 p-6 shadow-sm sm:p-8">
    <header>
        <h2 id="{{ $id }}-title" class="text-base font-semibold text-white">{{ $title }}</h2>
        <p class="mt-1 text-sm text-neutral-400">{{ $description }}</p>
    </header>

    {{ $slot }}
</section>
