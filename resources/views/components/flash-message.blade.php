<div
    x-data="{
        toasts: [],
        addToast(message, type = 'success', icon = '') {
            const id = Date.now();
            this.toasts.push({
                id,
                message,
                type,
                icon: icon || (type === 'success' ? 'fa fa-check' : type === 'error' ? 'fa fa-x' : 'fa fa-info')
            });
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 3000);
        }
    }"
    x-init="
        @if(session('success'))
            addToast('{{ session('success') }}', 'success', '{{ session('icon', 'fa fa-check') }}');
        @elseif(session('message'))
            addToast('{{ session('message') }}', 'success', '{{ session('icon', 'fa fa-check') }}');
        @elseif(session('error'))
            addToast('{{ session('error') }}', 'error', '{{ session('icon', 'fa fa-x') }}');
        @elseif(session('info'))
            addToast('{{ session('status') }}', 'info', '{{ session('icon', 'fa fa-info') }}');
        @elseif(session('warning'))
            addToast('{{ session('warning') }}', 'warning', '{{ session('icon', 'fa fa-exclamation-triangle') }}');
        @endif
    "
    @toast.window="addToast($event.detail.message, $event.detail.type, $event.detail.icon)"
    class="fixed z-50 right-4 bottom-4 flex flex-col space-y-2 max-w-sm"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition
            class="rounded-md px-6 py-3 text-white shadow-lg flex items-center space-x-2"
            :class="toast.type === 'success' ? 'bg-green-500 hover:bg-green-600' :
                    toast.type === 'error' ? 'bg-red-500 hover:bg-red-600' :
                    toast.type === 'status' ? 'bg-blue-500 hover:bg-blue-600' :
                    toast.type === 'warning' ? 'bg-yellow-500 hover:bg-yellow-600' : ''"
        >
            <span class="text-2xl"><i :class="toast.icon"></i></span>
            <p class="font-bold" x-text="toast.message"></p>
        </div>
    </template>
</div>
