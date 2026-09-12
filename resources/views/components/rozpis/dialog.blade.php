{{--
    The shell both rozpis panels sit in: the guide and the change history.

    A native <dialog> rather than <x-modal>: that component routes opening through an Alpine custom
    event and an x-teleport, and these panels are read-only text with no Livewire state in them.
    showModal() gives the backdrop, Esc, the focus trap and inertness for free, and cannot be broken
    by whatever else on the page Alpine is or is not managing.

    Only the header text, the width and the footer button differ between the two, so everything
    else - the backdrop-click handler, the chrome, the scrolling body - lives here once.
--}}
@props([
    'id',
    'icon',
    'title',
    'subtitle' => null,
    'width' => '48rem',
])

{{-- A backdrop click reports the dialog itself as the target; anything inside reports a child. --}}
<dialog id="{{ $id }}"
        onclick="if (event.target === this) this.close()"
        style="--dialog-width: {{ $width }}"
        class="w-[min(var(--dialog-width),92vw)] rounded-2xl border border-neutral-800 bg-neutral-900 p-0 text-neutral-200 shadow-2xl backdrop:bg-neutral-950/80 backdrop:backdrop-blur-sm">
    <form method="dialog" class="flex items-start justify-between gap-4 border-b border-neutral-800 px-6 py-4">
        <div>
            <h2 class="flex items-center gap-2 text-lg font-bold text-white">
                <i class="fa-solid {{ $icon }} text-sky-400"></i>
                {{ $title }}
            </h2>

            @if($subtitle)
                <p class="mt-1 text-xs text-neutral-400">{{ $subtitle }}</p>
            @endif
        </div>

        <button type="submit" aria-label="Zavrieť"
                class="shrink-0 rounded-lg p-2 text-neutral-500 transition hover:bg-neutral-800 hover:text-white">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </form>

    <div class="max-h-[70vh] overflow-y-auto px-6 py-5">
        {{ $slot }}
    </div>

    <form method="dialog" class="flex justify-end border-t border-neutral-800 px-6 py-4">
        {{ $footer }}
    </form>
</dialog>
