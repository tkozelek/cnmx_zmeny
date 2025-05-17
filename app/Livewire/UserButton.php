<?php

namespace App\Livewire;

use Livewire\Component;

class UserButton extends Component
{
    public $dayId;

    public $locked;

    public $users;

    public function mount($dayId, $locked, $users)
    {
        $this->dayId = $dayId;
        $this->locked = $locked;
        $this->users = $users;
    }

    public function addUser()
    {
        // Handle adding or removing user logic here
        // You can emit an event to parent component to update users list
        $this->dispatch('addUser');
    }

    public function render()
    {
        return view('livewire.user-button');
    }
}
