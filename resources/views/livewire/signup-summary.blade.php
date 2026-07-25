<div>
    @if($this->signupCounts->isNotEmpty())
        <section class="flex flex-col gap-3 pt-4">
            <h2 class="text-lg font-semibold text-neutral-100">Prehľad počtu zapísaných</h2>
            <x-table :headers="['Meno', 'Počet']" class="max-w-md">
                @foreach($this->signupCounts as $row)
                    <x-table-row>
                        <x-table-cell class="font-medium text-neutral-100">
                            {{ $row->lastname }} {{ Str::substr($row->name, 0, 1) }}.
                        </x-table-cell>
                        <x-table-cell class="text-right tabular-nums text-neutral-300">{{ $row->count }}</x-table-cell>
                    </x-table-row>
                @endforeach
            </x-table>
        </section>
    @endif
</div>
