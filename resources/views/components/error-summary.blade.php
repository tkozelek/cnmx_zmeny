{{-- Every validation error above the form, each linked to its field, focused on load so a
     screen reader starts here. `fields` maps an error key to its input id when the two differ. --}}
<div
    {{ $attributes->merge(['id' => 'error-summary']) }}
    role="alert"
    tabindex="-1"
    x-data
    x-init="$nextTick(() => $el.focus())"
    class="rounded-lg border border-rose-500/40 bg-rose-500/10 px-4 py-3 text-sm text-rose-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-400"
>
    <p class="flex items-center gap-2 font-semibold">
        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
        {{ $title }}
    </p>
    <ul class="mt-1.5 list-disc space-y-0.5 pl-6">
        @foreach($items as $item)
            <li>
                <a href="#{{ $item['id'] }}"
                   {{-- Following a fragment only scrolls; the field itself should get the focus. --}}
                   @click="const field = document.getElementById($el.hash.slice(1)); if (field) { $event.preventDefault(); field.focus(); field.scrollIntoView({ block: 'center' }) }"
                   class="underline underline-offset-2 hover:text-rose-200">{{ $item['message'] }}</a>
            </li>
        @endforeach
    </ul>
</div>
