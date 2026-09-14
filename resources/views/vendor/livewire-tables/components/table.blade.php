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
                'relative shadow-sm overflow-x-auto border border-neutral-800 rounded-lg bg-neutral-900' => $customAttributes['wrapper']['default'] ?? true
            ])
            ->except(['default','default-styling','default-colors']) }}
    >
        {{-- Table loading overlay and animated progress bar --}}
        <div wire:loading class="absolute inset-0 bg-neutral-950/40 backdrop-blur-sm z-20 flex items-center justify-center pointer-events-none transition-all duration-200">
            <div class="sticky left-1/2 -translate-x-1/2 inline-flex items-center gap-2.5 px-4 py-2 rounded-lg bg-neutral-900 border border-neutral-700 shadow-xl text-xs font-semibold text-neutral-200 tracking-wide">
                <i class="fa-solid fa-circle-notch fa-spin text-brand-400 text-sm"></i>
                <span>Načítavam...</span>
            </div>
        </div>
        <div wire:loading class="sticky left-0 top-0 inset-x-0 h-0.5 bg-neutral-800 overflow-hidden z-30 pointer-events-none">
            <div class="h-full bg-gradient-to-r from-brand-500 via-brand-300 to-brand-500 animate-pulse w-full"></div>
        </div>

        <table
            wire:key="{{ $tableName }}-table"
            wire:loading.class="blur-sm pointer-events-none select-none"
            {{ $attributes->merge($customAttributes['table'])
                ->class([
                    'min-w-full divide-y divide-neutral-800 transition-[filter] duration-200' => $customAttributes['table']['default'] ?? true
                ])
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
