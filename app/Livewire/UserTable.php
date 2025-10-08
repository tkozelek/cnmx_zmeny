<?php

namespace App\Livewire;

use App\Models\User;
use App\Notifications\UserAllowedToLogin;
use Livewire\Component;
use Livewire\WithPagination;

class UserTable extends Component
{
    use WithPagination;

    public $roles;

    public $selectedRole = 0;

    public $search = '';

    public $sortField = 'lastname';

    public $sortDirection = 'asc';

    public $querystring = ['sortField', 'sortDirection'];

    public function render()
    {
        $query = User::query();

        if ($this->selectedRole != 0) {
            $query->where('id_role', $this->selectedRole);
        }
        if ($this->search != '') {
            $query->whereAny(['name', 'lastname', 'email'], 'LIKE', '%'.$this->search.'%');
        }

        $users = $query->with('role')->orderBy($this->sortField, $this->sortDirection)->paginate(10);

        return view('livewire.user-table', [
            'users' => $users,
        ]);
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }

    public function accept(User $user)
    {
        $user->id_role = config('constants.roles.brigadnik');
        $user->save();

        $user->notify(new UserAllowedToLogin($user));
        $this->dispatch('toast', message: 'Používateľ overený.');
    }

    public function deny(User $user)
    {
        $user->id_role = config('constants.roles.zablokovany');
        $user->save();
    }
}
