@props(['busyLabel'])

{{-- Disabled a tick after submit (disabling synchronously can cancel it); bfcache restores it on Back. --}}
<button
    type="submit"
    x-data="{ busy: false }"
    x-init="$el.form.addEventListener('submit', () => setTimeout(() => busy = true)); window.addEventListener('pageshow', () => busy = false)"
    x-bind:disabled="busy"
    x-bind:aria-busy="busy"
    {{ $attributes->class('flex min-h-12 w-full items-center justify-center rounded-lg bg-neutral-100 px-6 py-3 text-base font-semibold text-neutral-900 transition hover:bg-white active:scale-[0.98] disabled:cursor-wait disabled:opacity-70 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-900') }}
>
    <span class="grid">
        <span class="[grid-area:1/1]" x-bind:class="busy && 'invisible'" x-bind:aria-hidden="busy">{{ $slot }}</span>
        <span class="invisible [grid-area:1/1]" x-bind:class="busy && '!visible'" x-bind:aria-hidden="! busy" aria-hidden="true">{{ $busyLabel }}</span>
    </span>
</button>
