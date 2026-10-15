@aware(['isTailwind','isBootstrap', 'localisationPath'])
@if ($this->offlineIndicatorIsEnabled())
    @if ($isTailwind)
        <div wire:offline.class.remove="hidden" class="hidden">
            <div class="rounded-lg border border-rose-500/40 bg-rose-500/10 p-4 mb-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <x-heroicon-s-x-circle class="h-5 w-5 text-rose-400" />
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-rose-300">
                            {{ __($localisationPath.'You are not connected to the internet') }}.
                        </h3>
                    </div>
                </div>
            </div>
        </div>
    @elseif ($isBootstrap)
        <div wire:offline.class.remove="d-none" class="d-none">
            <div class="alert alert-danger d-flex align-items-center">
                <x-heroicon-s-x-circle class="laravel-livewire-tables-btn-md" />
                <span class="d-inline-block ml-2">{{ __($localisationPath.'You are not connected to the internet') }}.
                </span>
            </div>
        </div>
    @endif
@endif
