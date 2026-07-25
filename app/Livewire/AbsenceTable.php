<?php

namespace App\Livewire;

use App\Models\Absence;
use App\Services\AbsenceService;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class AbsenceTable extends Component
{
    use WithPagination;

    public string $search = '';

    public string $tab = 'active';

    public function render()
    {
        return view('livewire.absence-table', [
            'absences' => $this->absences(),
        ]);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->resetPage();
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

    #[Computed]
    public function isAdmin(): bool
    {
        return auth()->user()?->hasRole('admin') ?? false;
    }

    private function absences(): Paginator
    {
        $user = auth()->user();

        return Absence::with('user')
            ->when($this->tab === 'mine', fn (Builder $q) => $q->where('user_id', $user->id))
            ->when($this->tab === 'active', fn (Builder $q) => $q->active())
            ->when($this->tab === 'past', fn (Builder $q) => $q->past())
            ->when(! $this->isAdmin && $this->tab !== 'mine', function (Builder $q) {
                // Non-admins see active team absences without sensitive detail
                $q->active();
            })
            ->when($this->search !== '', function (Builder $q) {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $sub) use ($term) {
                    $sub->where('reason', 'LIKE', $term)
                        ->orWhereHas('user', function (Builder $userQuery) use ($term) {
                            $userQuery->whereAny(['name', 'lastname', 'email'], 'LIKE', $term);
                        });
                });
            })
            ->orderByDesc('date_from')
            ->paginate(10);
    }
}
