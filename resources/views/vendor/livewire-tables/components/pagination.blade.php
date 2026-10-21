@aware(['isTailwind','isBootstrap','isBootstrap4', 'isBootstrap5', 'localisationPath'])
@props(['currentRows'])

@includeWhen(
    $this->hasConfigurableAreaFor('before-pagination'), 
    $this->getConfigurableAreaFor('before-pagination'), 
    $this->getParametersForConfigurableArea('before-pagination')
)

<div {{ $this->getPaginationWrapperAttributesBag() }}>
    @if ($this->paginationVisibilityIsEnabled())
        <div class="mt-4 px-4 md:p-0 sm:flex justify-between items-center space-y-4 sm:space-y-0 text-sm text-neutral-400">
            {{-- Polite live region: a search or a page change is otherwise silent for screen readers. --}}
            <div aria-live="polite" aria-atomic="true">
                @if ($this->paginationIsEnabled && $this->isPaginationMethod('standard') && $currentRows->lastPage() > 1 && $this->showPaginationDetails)
                    <p class="paged-pagination-results leading-5 text-neutral-400">
                        <span>Zobrazených</span>
                        <span class="font-bold text-neutral-100">{{ $currentRows->firstItem() }}</span>
                        <span>až</span>
                        <span class="font-bold text-neutral-100">{{ $currentRows->lastItem() }}</span>
                        <span>z</span>
                        <span class="font-bold text-neutral-100"><span x-text="paginationTotalItemCount"></span></span>
                        <span>výsledkov</span>
                    </p>
                @elseif ($this->showPaginationDetails)
                    @php($resultCount = $currentRows->count())
                    <p class="total-pagination-results leading-5 text-neutral-400">
                        <span>Celkom</span>
                        <span class="font-bold text-neutral-100">{{ $resultCount }}</span>
                        <span>{{ match (true) { $resultCount === 1 => 'výsledok', $resultCount >= 2 && $resultCount <= 4 => 'výsledky', default => 'výsledkov' } }}</span>
                    </p>
                @endif
            </div>

            @if ($this->paginationIsEnabled)
                {{ $currentRows->links('livewire-tables::specific.tailwind.'.(!$this->isPaginationMethod('standard') ? 'simple-' : '').'pagination') }}
            @endif
        </div>
    @endif
</div>

@includeWhen(
    $this->hasConfigurableAreaFor('after-pagination'), 
    $this->getConfigurableAreaFor('after-pagination'), 
    $this->getParametersForConfigurableArea('after-pagination')
)
