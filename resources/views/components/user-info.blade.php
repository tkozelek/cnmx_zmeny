@props(['text'])

<p class="mt-1 text-lg tracking-wide">
    <strong>{{ $text }}:</strong>
    {{ $slot }}
</p>
