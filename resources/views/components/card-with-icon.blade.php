@props(['icon', 'title', 'text'])

<div {{ $attributes->merge(['class' => 'reveal flex items-start gap-4 rounded-lg border border-neutral-800 bg-neutral-900 p-8 transition hover:border-neutral-700']) }}>
    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border border-brand-500/60 bg-brand-500/10 text-lg text-brand-400">
        <i class="fa-solid {{ $icon }}" aria-hidden="true"></i>
    </div>
    <div>
        <h3 class="mb-2 text-xl font-bold text-neutral-100">{{ $title }}</h3>
        <p class="text-neutral-400">{{ $text }}</p>
    </div>
</div>
