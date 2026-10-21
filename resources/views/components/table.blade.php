@props(['headers' => []])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-neutral-800 bg-neutral-900 shadow-sm']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-neutral-300">
            @if(isset($head) || filled($headers))
                <thead class="border-b border-neutral-800 bg-neutral-900/60 text-xs font-medium uppercase tracking-wide text-neutral-400">
                    <tr>
                        @isset($head)
                            {{ $head }}
                        @else
                            @foreach($headers as $header)
                                <x-table-cell-header>{{ $header }}</x-table-cell-header>
                            @endforeach
                        @endisset
                    </tr>
                </thead>
            @endif

            <tbody class="divide-y divide-neutral-800">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="border-t border-neutral-800 px-4 py-3">
            {{ $footer }}
        </div>
    @endisset
</div>
