{{-- Session messages are added a beat after load, so screen readers have registered the live region before it changes. --}}
<div
    x-data="{
        toasts: [],
        addToast(message, type = 'success', icon = '') {
            if (!message) return;
            const id = Date.now() + Math.random();
            this.toasts.push({
                id,
                message,
                type,
                icon: icon || (
                    type === 'success' ? 'fa-solid fa-circle-check text-emerald-400' :
                    type === 'error' ? 'fa-solid fa-circle-xmark text-rose-400' :
                    type === 'warning' ? 'fa-solid fa-triangle-exclamation text-amber-400' :
                    'fa-solid fa-circle-info text-brand-400'
                )
            });
            this.resume(id);
        },
        timers: {},
        // Confirmations fade on their own; an error stays until closed - it is something to act on.
        resume(id) {
            const toast = this.toasts.find(t => t.id === id);
            if (!toast || toast.type === 'error') return;
            this.timers[id] = setTimeout(() => this.removeToast(id), 8000);
        },
        pause(id) {
            clearTimeout(this.timers[id]);
        },
        removeToast(id) {
            this.pause(id);
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
        handleToastEvent(e) {
            let d = e.detail;
            if (Array.isArray(d)) d = d[0] || {};
            if (typeof d === 'string') {
                this.addToast(d);
                return;
            }
            if (d && typeof d === 'object') {
                this.addToast(d.message || d.title || '', d.type || 'success', d.icon || '');
            }
        }
    }"
    x-init="
        setTimeout(() => {
        @if(session('success'))
            addToast(@json(session('success')), 'success', @json(session('icon', '')));
        @elseif(session('message'))
            addToast(@json(session('message')), 'success', @json(session('icon', '')));
        @endif
        @if(session('error'))
            addToast(@json(session('error')), 'error', @json(session('icon', '')));
        @endif
        @if(session('status') && !session('success') && !session('message'))
            addToast(@json(session('status')), 'info', @json(session('icon', '')));
        @endif
        @if(session('info'))
            addToast(@json(session('info')), 'info', @json(session('icon', '')));
        @endif
        @if(session('warning'))
            addToast(@json(session('warning')), 'warning', @json(session('icon', '')));
        @endif
        }, 500);
    "
    @toast.window="handleToastEvent($event)"
    role="status" aria-live="polite" class="fixed bottom-4 inset-x-4 sm:inset-x-auto sm:right-4 z-[110] flex flex-col gap-2 sm:w-full sm:max-w-sm pointer-events-none"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 translate-y-2"
            @mouseenter="pause(toast.id)"
            @mouseleave="resume(toast.id)"
            @focusin="pause(toast.id)"
            @focusout="resume(toast.id)"
            class="pointer-events-auto flex items-center gap-2.5 rounded-xl border border-neutral-800 bg-neutral-900/95 backdrop-blur-md px-4 py-3 shadow-2xl text-neutral-100"
            :class="{
                'border-emerald-500/50': toast.type === 'success',
                'border-rose-500/50': toast.type === 'error',
                'border-amber-500/50': toast.type === 'warning',
                'border-brand-500/60': toast.type === 'info' || toast.type === 'status'
            }"
        >
            <i class="shrink-0 text-sm" :class="toast.icon" aria-hidden="true"></i>
            <p class="flex-1 min-w-0 text-sm font-medium leading-snug break-words" x-text="toast.message" :title="toast.message"></p>

            <button
                type="button"
                @click="removeToast(toast.id)"
                class="shrink-0 text-neutral-400 hover:text-white transition-colors focus:outline-none ml-2"
                aria-label="Zavrieť oznam"
            >
                <i class="fa-solid fa-xmark text-xs" aria-hidden="true"></i>
            </button>
        </div>
    </template>
</div>
