<x-layout title="Pozície" description="Pracovné pozície, z ktorých sa skladá rozpis zmien.">
    <div class="container mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-6">

            <x-page-header
                icon="fa-list-check"
                title="Pozície"
                subtitle="Pracovné pozície tohto kina - z nich sa skladá rozpis zmien."
            />

            <livewire:position-list />
        </div>
    </div>
</x-layout>
