<x-layout title="POZÍCIE">
    <div class="container mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6">

            <div class="flex flex-col gap-4 border-b border-neutral-800 pb-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="flex items-center gap-2.5 text-2xl font-bold text-white">
                        <i class="fa-solid fa-list-check text-sky-400"></i>
                        Pozície
                    </h1>
                    <p class="mt-1 text-xs text-neutral-400">
                        Pracovné pozície tohto kina — z nich sa skladá rozpis zmien.
                    </p>
                </div>
            </div>

            <livewire:position-list />
        </div>
    </div>
</x-layout>
