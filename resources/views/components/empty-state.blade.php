@props([
    'message' => 'Žiadne dáta k dispozícii.',
    'icon' => 'fa-folder-open',
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-dashed border-neutral-700 bg-neutral-900/50 px-4 py-8 text-center']) }}>
    <i class="fa-solid {{ $icon }} text-4xl text-neutral-500" aria-hidden="true"></i>
    <h3 class="mt-3 font-medium text-neutral-300">{{ $message }}</h3>

    @if($slot->isNotEmpty())
        <p class="mt-1 text-sm text-neutral-400">{{ $slot }}</p>
    @endif

    @isset($action)
        <div class="mt-6">{{ $action }}</div>
    @endisset
</div>
