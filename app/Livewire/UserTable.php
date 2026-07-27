<?php

namespace App\Livewire;

use App\Enums\Role;
use App\Models\Team;
use App\Models\User;
use App\Notifications\UserAllowedToLogin;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * The admin user table: search, sort, approve or block a membership.
 *
 * Scoped to the current cinema — `team_user` is what makes somebody a member, and the role
 * filter runs through Spatie, whose `model_has_roles.team_id` scopes the answer to the same
 * team.
 */
class UserTable extends Component
{
    use WithPagination;

    public string $selectedRole = '';

    public string $search = '';

    public string $sortField = 'lastname';

    public string $sortDirection = 'asc';

    /** @var array<int, string> */
    protected $queryString = ['sortField', 'sortDirection', 'search'];

    public function render()
    {
        return view('livewire.user-table', [
            'users' => $this->users(),
            'roles' => Role::cases(),
        ]);
    }

    public function sortBy(string $field): void
    {
        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc'
            ? 'desc'
            : 'asc';

        $this->sortField = $field;
    }

    /** Approve a pending membership and tell them they may log in. */
    public function accept(User $user): void
    {
        $this->authorize('update', $user);

        $this->team()->users()->updateExistingPivot($user->id, ['approved_at' => now()]);
        $user->forgetApprovedTeamsCache();

        $user->notify(new UserAllowedToLogin($user));
        $this->dispatch('toast', message: 'Používateľ overený.');
    }

    /**
     * Refuse someone. Deactivates the account rather than deleting it — payroll rows are
     * RESTRICT, and `is_active = false` is what the legacy "blocked" role meant.
     */
    public function deny(User $user): void
    {
        $this->authorize('update', $user);

        $user->update(['is_active' => false]);
        $user->forgetApprovedTeamsCache();

        $this->dispatch('toast', message: 'Používateľ zablokovaný.');
    }

    /**
     * @return Paginator<int, User>
     */
    private function users(): Paginator
    {
        return $this->team()->users()
            ->with('roles')
            ->when($this->selectedRole !== '', fn (Builder $query) => $query->role($this->selectedRole))
            ->when($this->search !== '', fn (Builder $query) => $query->whereAny(
                ['name', 'lastname', 'email'],
                'LIKE',
                '%'.$this->search.'%',
            ))
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate(10);
    }

    private function team(): Team
    {
        return app(Team::class);
    }

    /** Resetting the page on a new search stops "page 4 of 1 result" landing on nothing. */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedSelectedRole(): void
    {
        $this->resetPage();
    }
}
