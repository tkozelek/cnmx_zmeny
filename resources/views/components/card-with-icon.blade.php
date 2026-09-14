@props(['icon', 'title', 'text', 'accent' => 'sky'])

{{--
    Accent classes are spelled out in full so Tailwind's static scanner can see them -
    a PHP-interpolated "border-{$accent}-500/30" never appears literally in source and
    would silently produce no CSS.
--}}
@php
    $accents = [
        'sky' => 'border-brand-500/60 bg-brand-500/10 text-brand-400',
        'emerald' => 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400',
        'amber' => 'border-amber-500/30 bg-amber-500/10 text-amber-400',
        'rose' => 'border-rose-500/30 bg-rose-500/10 text-rose-400',
    ];
@endphp

<div {{ $attributes->merge(['class' => 'reveal flex items-start gap-4 rounded-xl border border-neutral-800 bg-neutral-900 p-8 transition hover:border-neutral-700']) }}>
    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full border text-lg {{ $accents[$accent] ?? $accents['sky'] }}">
        <i class="fa-solid {{ $icon }}"></i>
    </div>
    <div>
        <h4 class="mb-2 text-xl font-bold text-neutral-100">{{ $title }}</h4>
        <p class="text-neutral-400">{{ $text }}</p>
    </div>
</div>
