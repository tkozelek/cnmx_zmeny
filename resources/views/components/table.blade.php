{{-- `:headers` for plain labels, the `head` slot when header cells need markup. --}}
@props(['headers' => []])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg bg-slate-800 shadow-xl']) }}>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm text-slate-300">
            @if(isset($head) || filled($headers))
                <thead class="bg-slate-700/50 text-xs uppercase text-slate-200">
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

            <tbody class="divide-y divide-slate-700">
                {{ $slot }}
            </tbody>
        </table>
    </div>

    @isset($footer)
        <div class="border-t border-slate-700 bg-slate-800 p-5">
            {{ $footer }}
        </div>
    @endisset
</div>
