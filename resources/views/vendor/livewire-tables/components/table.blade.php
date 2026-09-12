@aware([ 'tableName','isTailwind','isBootstrap'])

@php
    $customAttributes = [
        'wrapper' => $this->getTableWrapperAttributes(),
        'table' => $this->getTableAttributes(),
        'thead' => $this->getTheadAttributes(),
        'tbody' => $this->getTbodyAttributes(),
    ];
@endphp

@if ($isTailwind)
    <div
        wire:key="{{ $tableName }}-twrap"
        {{ $attributes->merge($customAttributes['wrapper'])
            ->class([
                'shadow-2xl overflow-x-auto border border-neutral-800 rounded-xl bg-neutral-900' => $customAttributes['wrapper']['default'] ?? true
            ])
            ->except(['default','default-styling','default-colors']) }}
    >
        <table
            wire:key="{{ $tableName }}-table"
            {{ $attributes->merge($customAttributes['table'])
                ->class(['min-w-full divide-y divide-neutral-800' => $customAttributes['table']['default'] ?? true])
                ->except(['default','default-styling','default-colors']) }}
        >
            <thead wire:key="{{ $tableName }}-thead"
                {{ $attributes->merge($customAttributes['thead'])
                    ->class([
                        'bg-neutral-950 border-b border-neutral-800' => $customAttributes['thead']['default'] ?? true
                    ])
                    ->except(['default','default-styling','default-colors']) }}
            >
                <tr>
                    {{ $thead }}
                </tr>
            </thead>

            <tbody
                wire:key="{{ $tableName }}-tbody"
                id="{{ $tableName }}-tbody"
                {{ $attributes->merge($customAttributes['tbody'])
                        ->class([
                            'bg-neutral-900 divide-y divide-neutral-800/60' => $customAttributes['tbody']['default'] ?? true
                        ])
                        ->except(['default','default-styling','default-colors']) }}
            >
                {{ $slot }}
            </tbody>

            @isset($tfoot)
                <tfoot wire:key="{{ $tableName }}-tfoot">
                    {{ $tfoot }}
                </tfoot>
            @endisset
        </table>
    </div>
@endif
