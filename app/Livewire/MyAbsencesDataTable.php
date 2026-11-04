<?php

namespace App\Livewire;

use App\Models\Absence;
use App\Services\AbsenceService;
use App\Support\SlovakDate;
use App\Traits\FormatsAbsenceColumns;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class MyAbsencesDataTable extends DataTableComponent
{
    /** Both absence tables share /dovolenka - distinct names keep their element ids unique. */
    public string $tableName = 'my-absences';

    use FormatsAbsenceColumns;

    /** @var class-string<Absence> */
    protected $model = Absence::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setTheme('tailwind')
            // The sort is already visible on the column header; a pill for it was just noise.
            ->setSortingPillsDisabled()
            ->setPerPageAccepted([5, 10, 25])
            ->setPerPage(5)
            ->setColumnSelectStatus(false)
            ->setFilterPillsStatus(true)
            ->setFilterLayoutSlideDown()
            // The Stav/Akcie columns read fields (status, team_id, user_id, updated_at, ...) off
            // $row directly rather than through a registered Column, so the package's column-based
            // SELECT projection would otherwise drop them - select the whole row instead of
            // chasing every field the trait happens to touch.
            ->setAdditionalSelects(['absences.*'])
            ->setSearchDebounce(250)
            ->setSearchPlaceholder('Vyhľadať v absenciách…')
            ->setLoadingPlaceholderContent('Načítavam absencie...')
            // Phones: one stacked column instead of six - see FormatsAbsenceColumns::mobileCardColumn().
            ->setCollapsingColumnsDisabled()
            ->setThAttributes(fn (Column $column): array => $this->mobileCardAttributes($column, cell: false))
            ->setTdAttributes(fn (Column $column): array => $this->mobileCardAttributes($column, cell: true))
            ->setEmptyMessage('Nemáš evidované žiadne absencie.');
    }

    /** @return Builder<Absence> */
    public function builder(): Builder
    {
        return Absence::query()
            ->where('user_id', auth()->id())
            ->when($this->getSorts() === [], fn (Builder $query) => $query->orderByRelevance());
    }

    /** @return list<Filter> */
    public function filters(): array
    {
        return [
            SelectFilter::make('Stav')
                ->setFilterDefaultValue('active')
                ->options([
                    '' => 'Všetky absencie',
                    'active' => 'Aktívne',
                    'past' => 'Vypršané / Deaktivované',
                ])
                ->filter(function (Builder $builder, string $value) {
                    match ($value) {
                        'active' => $builder->active(),
                        'past' => $builder->past(),
                        default => null,
                    };
                }),
        ];
    }

    /** @return list<Column> */
    public function columns(): array
    {
        return [
            $this->mobileCardColumn(),

            Column::make('Začiatok', 'date_from')
                ->collapseOnMobile()
                ->sortable()
                ->format(fn ($value) => '<span class="font-semibold text-neutral-100">'.($value ? SlovakDate::full($value) : '').'</span>')
                ->html(),

            Column::make('Koniec', 'date_to')
                ->collapseOnMobile()
                ->sortable()
                ->format(fn ($value, $row) => $this->formatEndColumn($row))
                ->html(),

            Column::make('Stav', 'status')
                ->collapseOnMobile()
                ->sortable(function (Builder $query, string $direction) {
                    $today = now()->toDateString();

                    return $query->orderByRaw(
                        "CASE 
                            WHEN status = 'cancelled' THEN 3
                            WHEN date_to < '{$today}' THEN 2
                            ELSE 1
                         END {$direction}"
                    );
                })
                ->format(fn ($value, $row) => $this->formatStatusColumn($row))
                ->html(),

            Column::make('Dôvod', 'reason')
                ->collapseOnMobile()
                ->searchable()
                ->format(fn ($value) => '<span class="max-w-xs truncate text-neutral-300">'.e($value ?: '-').'</span>')
                ->html(),

            Column::make('Nahlásené', 'created_at')
                ->collapseOnMobile()
                ->sortable()
                ->format(fn ($value) => '<span class="text-neutral-400 text-xs">'.($value ? SlovakDate::dateTime($value) : '').'</span>')
                ->html(),

            Column::make('Akcie', 'id')
                ->collapseOnMobile()
                ->format(fn ($value, $row) => $this->formatActionsColumn($row))
                ->html(),
        ];
    }

    public function endAbsence(int $id, AbsenceService $service): void
    {
        $absence = Absence::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('end', $absence);

        $service->end($absence);

        $this->dispatch('toast', message: 'Absencia ukončená.');
    }

    public function deleteAbsence(int $id): void
    {
        $absence = Absence::where('user_id', auth()->id())->findOrFail($id);
        $this->authorize('delete', $absence);

        $absence->delete();

        $this->dispatch('toast', message: 'Absencia vymazaná.', type: 'info');
    }
}
