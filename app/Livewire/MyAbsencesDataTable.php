<?php

namespace App\Livewire;

use App\Models\Absence;
use App\Services\AbsenceService;
use App\Traits\FormatsAbsenceColumns;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class MyAbsencesDataTable extends DataTableComponent
{
    use FormatsAbsenceColumns;

    protected $model = Absence::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setTheme('tailwind')
            ->setDefaultSort('date_from', 'desc')
            ->setPerPageAccepted([5, 10, 25])
            ->setPerPage(5)
            ->setColumnSelectStatus(false)
            ->setFilterPillsStatus(false)
            ->setFilterLayoutSlideDown()
            // The Stav/Akcie columns read fields (status, team_id, user_id, updated_at, ...) off
            // $row directly rather than through a registered Column, so the package's column-based
            // SELECT projection would otherwise drop them - select the whole row instead of
            // chasing every field the trait happens to touch.
            ->setAdditionalSelects(['absences.*'])
            ->setSearchPlaceholder('Vyhľadať v absenciách...')
            ->setEmptyMessage('Nemáš evidované žiadne absencie.');
    }

    public function builder(): Builder
    {
        return Absence::query()
            ->where('user_id', auth()->id());
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('Stav')
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

    public function columns(): array
    {
        return [
            Column::make('Začiatok', 'date_from')
                ->sortable()
                ->format(fn ($value) => '<span class="font-semibold text-neutral-100">'.$value?->format('d.m.Y').'</span>')
                ->html(),

            Column::make('Koniec', 'date_to')
                ->sortable()
                ->format(fn ($value, $row) => '<span class="text-neutral-300">'.($row->isOpenEnded() ? 'Trvalá' : $value?->format('d.m.Y')).'</span>')
                ->html(),

            Column::make('Stav', 'status')
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
                ->searchable()
                ->format(fn ($value) => '<span class="max-w-xs truncate text-neutral-300">'.e($value ?: '-').'</span>')
                ->html(),

            Column::make('Nahlásené', 'created_at')
                ->sortable()
                ->format(fn ($value) => '<span class="text-neutral-400 text-xs">'.$value?->format('d.m.Y H:i').'</span>')
                ->html(),

            Column::make('Akcie', 'id')
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

        $this->dispatch('toast', message: 'Absencia vymazaná.', type: 'error');
    }
}
