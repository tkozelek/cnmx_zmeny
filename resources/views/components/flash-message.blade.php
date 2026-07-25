<div
    x-data="{
        toasts: [],
        addToast(message, type = 'success', icon = '') {
            const id = Date.now();
            this.toasts.push({
                id,
                message,
                type,
                icon: icon || (
                    type === 'success' ? 'fa-solid fa-circle-check text-emerald-400' :
                    type === 'error' ? 'fa-solid fa-circle-xmark text-rose-400' :
                    type === 'warning' ? 'fa-solid fa-triangle-exclamation text-amber-400' :
                    'fa-solid fa-circle-info text-sky-400'
                )
            });
            setTimeout(() => {
                this.removeToast(id);
            }, 4000);
        },
        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        }
    }"
    x-init="
        @if(session('success'))
            addToast(@json(session('success')), 'success', @json(session('icon', '')));
        @elseif(session('message'))
            addToast(@json(session('message')), 'success', @json(session('icon', '')));
        @elseif(session('error'))
            addToast(@json(session('error')), 'error', @json(session('icon', '')));
        @elseif(session('info') || session('status'))
            addToast(@json(session('status') ?? session('info')), 'info', @json(session('icon', '')));
        @elseif(session('warning'))
            addToast(@json(session('warning')), 'warning', @json(session('icon', '')));
        @endif
    "
    @toast.window="addToast($event.detail.message, $event.detail.type, $event.detail.icon)"
    class="fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-xs w-full px-4 sm:px-0"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            class="flex items-center gap-2.5 rounded-lg border border-neutral-800 bg-neutral-900/95 backdrop-blur-sm px-3 py-2 shadow-lg text-neutral-100"
            :class="{
                'border-emerald-500/50': toast.type === 'success',
                'border-rose-500/50': toast.type === 'error',
                'border-amber-500/50': toast.type === 'warning',
                'border-sky-500/50': toast.type === 'info' || toast.type === 'status'
            }"
        >
            <i class="shrink-0 text-sm" :class="toast.icon"></i>
            <p class="flex-1 min-w-0 text-xs font-medium leading-snug truncate" x-text="toast.message" :title="toast.message"></p>

            <button
                type="button"
                @click="removeToast(toast.id)"
                class="shrink-0 text-neutral-500 hover:text-white transition-colors focus:outline-none"
                aria-label="Zavrieť oznam"
            >
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>
    </template>
</div>
