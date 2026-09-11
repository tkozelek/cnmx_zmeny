<?php

namespace App\Livewire;

use App\Models\Absence;
use App\Models\Team;
use App\Services\AbsenceService;
use App\Traits\FormatsAbsenceColumns;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class AbsencesDataTable extends DataTableComponent
{
    use FormatsAbsenceColumns;

    protected $model = Absence::class;

    /**
     * The component asserts its own permission.
     *
     * It is also gated where it is rendered (holiday/index.blade.php), and Livewire snapshots are signed, so
     * this is belt and braces - but a view-level @can is the only thing standing between an
     * employee and every colleague's absence record, and that kind of gate is easy to lose in a refactor.
     */
    public function mount(): void
    {
        $this->authorize('viewAny', Absence::class);
    }

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setTheme('tailwind')
            ->setDefaultSort('date_from', 'desc')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPerPage(10)
            ->setColumnSelectStatus(false)
            ->setFilterPillsStatus(false)
            ->setFilterLayoutSlideDown()
            // The Stav/Akcie columns read fields (status, team_id, user_id, updated_at, ...) off
            // $row directly rather than through a registered Column, so the package's column-based
            // SELECT projection would otherwise drop them - select the whole row instead of
            // chasing every field the trait happens to touch.
            ->setAdditionalSelects(['absences.*'])
            ->setSearchPlaceholder('Vyhľadať absenciu...')
            ->setEmptyMessage('Žiadne absencie neboli nájdené.');
    }

    public function builder(): Builder
    {
        $team = app(Team::class);

        return Absence::query()
            ->where('team_id', $team->id)
            ->with('user');
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

            SelectFilter::make('Zobraziť')
                ->options([
                    '' => 'Všetci zamestnanci',
                    'mine' => 'Iba moje absencie',
                ])
                ->filter(function (Builder $builder, string $value) {
                    if ($value === 'mine') {
                        $builder->where('user_id', auth()->id());
                    }
                }),

            DateFilter::make('Od dátumu')
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('date_to', '>=', $value);
                }),

            DateFilter::make('Do dátumu')
                ->filter(function (Builder $builder, string $value) {
                    $builder->where('date_from', '<=', $value);
                }),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('Meno a Priezvisko', 'user.lastname')
                ->sortable()
                ->searchable(fn (Builder $query, string $term) => $query->whereHas('user', fn (Builder $q) => $q->whereAny(['name', 'lastname', 'email'], 'LIKE', "%{$term}%")))
                ->format(function ($value, $row) {
                    $fullName = trim(($row->user?->name ?? '').' '.($row->user?->lastname ?? ''));

                    return '<span class="font-bold text-white">'.e($fullName ?: '-').'</span>';
                })
                ->html(),

            Column::make('Začiatok', 'date_from')
                ->sortable()
                ->format(fn ($value) => '<span class="text-neutral-300">'.$value?->format('d.m.Y').'</span>')
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
                ->format(fn ($value, $row) => $this->formatActionsColumn($row, asManager: true))
                ->html(),
        ];
    }

    public function endAbsence(int $id, AbsenceService $service): void
    {
        $absence = Absence::findOrFail($id);
        $this->authorize('end', $absence);

        $service->end($absence);

        $this->dispatch('toast', message: 'Absencia ukončená.');
    }

    public function deleteAbsence(int $id): void
    {
        $absence = Absence::findOrFail($id);
        $this->authorize('delete', [$absence, true]);

        $absence->delete();

        $this->dispatch('toast', message: 'Absencia vymazaná.', type: 'error');
    }
}
