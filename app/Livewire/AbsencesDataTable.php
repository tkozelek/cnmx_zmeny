<?php

namespace App\Livewire;

use App\Models\Absence;
use App\Models\Team;
use App\Services\AbsenceService;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\DateFilter;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

class AbsencesDataTable extends DataTableComponent
{
    protected $model = Absence::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id')
            ->setTheme('tailwind')
            ->setDefaultSort('date_from', 'desc')
            ->setPerPageAccepted([10, 25, 50, 100])
            ->setPerPage(10)
            ->setColumnSelectStatus(true)
            ->setFilterPillsStatus(true)
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
            SelectFilter::make('Stav absencie')
                ->options([
                    '' => 'Všetky absencie',
                    'active' => 'Aktívne',
                    'past' => 'Vypršané',
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

                    return '<span class="font-bold text-white">'.e($fullName ?: '—').'</span>';
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

            Column::make('Stav', 'id')
                ->format(function ($value, $row) {
                    $isActive = $row->date_to->gte(now()->startOfDay());

                    if ($isActive) {
                        return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/30">Aktívna</span>';
                    }

                    return '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 text-xs font-semibold rounded-full bg-neutral-800 text-neutral-400 border border-neutral-700">Vypršaná</span>';
                })
                ->html(),

            Column::make('Dôvod', 'reason')
                ->searchable()
                ->format(fn ($value) => '<span class="max-w-xs truncate text-neutral-300">'.e($value ?: '—').'</span>')
                ->html(),

            Column::make('Akcie', 'id')
                ->format(function ($value, $row) {
                    $isMine = $row->user_id === auth()->id();
                    $isAdmin = auth()->user()?->hasRole('admin');
                    $isActive = $row->date_to->gte(now()->startOfDay());

                    $buttons = '';

                    if (($isMine || $isAdmin) && $isActive) {
                        $buttons .= '<button wire:click="endAbsence('.$row->id.')" title="Ukončiť absenciu" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-semibold px-3.5 py-2 text-xs shadow-sm transition"><i class="fa-solid fa-xmark text-xs"></i> Ukončiť</button>';
                    }

                    if ($isMine || $isAdmin) {
                        $buttons .= '<button wire:click="deleteAbsence('.$row->id.')" title="Vymazať absenciu" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-rose-600 hover:bg-rose-500 text-white font-bold shadow-sm transition"><i class="fa-solid fa-trash text-xs"></i></button>';
                    }

                    return '<div class="flex items-center justify-start gap-2">'.$buttons.'</div>';
                })
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
        $this->authorize('delete', $absence);

        $absence->delete();

        $this->dispatch('toast', message: 'Absencia vymazaná.', type: 'error');
    }
}
