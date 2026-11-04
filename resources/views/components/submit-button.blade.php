@props(['busyLabel', 'variant' => 'primary'])

{{-- Disabled a tick after submit (disabling synchronously can cancel it) unless a confirm() cancelled it; bfcache restores it on Back. --}}
<button
    type="submit"
    x-data="{ busy: false }"
    x-init="$el.form.addEventListener('submit', (event) => setTimeout(() => busy = ! event.defaultPrevented)); window.addEventListener('pageshow', () => busy = false)"
    x-bind:disabled="busy"
    x-bind:aria-busy="busy"
    {{ $attributes->class([
        'items-center justify-center font-semibold transition disabled:cursor-wait disabled:opacity-70',
        'flex rounded-lg min-h-12 w-full bg-neutral-100 px-6 py-3 text-base text-neutral-900 hover:bg-white active:scale-[0.98] focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400 focus-visible:ring-offset-2 focus-visible:ring-offset-neutral-900' => $variant === 'primary',
        'inline-flex rounded-lg min-h-11 border border-neutral-700 bg-neutral-900 px-4 text-sm text-neutral-100 shadow-sm hover:border-brand-500/60 hover:bg-neutral-800 hover:text-white' => $variant === 'secondary',
        'inline-flex' => $variant === 'custom',
    ]) }}
>
    <span class="grid">
        <span class="[grid-area:1/1]" x-bind:class="busy && 'invisible'" x-bind:aria-hidden="busy">{{ $slot }}</span>
        <span class="invisible [grid-area:1/1]" x-bind:class="busy && '!visible'" x-bind:aria-hidden="! busy" aria-hidden="true">{{ $busyLabel }}</span>
    </span>
</button>
