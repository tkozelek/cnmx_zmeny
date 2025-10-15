@props(['message' => 'Žiadne dáta k dispozícii.'])

<div class="text-center py-8 px-4 bg-slate-900/50 rounded-lg border border-dashed border-slate-700">
    <svg class="mx-auto h-12 w-12 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path vector-effect="non-scaling-stroke" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
    </svg>
    <h3 class="mt-2 text-md font-medium text-slate-300">{{ $message }}</h3>
</div>
