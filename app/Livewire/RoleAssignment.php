<?php

namespace App\Livewire;

use App\Models\Week;
use Livewire\Component;

class RoleAssignment extends Component
{
    public $boxes = [];
    public $items = [];
    public $currentDay = 0;
    public $days = [];
    public $roles = ['uv', 'buf', 'uv2'];


    public function mount(Week $week)
    {
        $this->days = $week->days()->with('users')->orderBy('date')->get();
    }

    public function render()
    {
        $users = $this->days[$this->currentDay]->users;
        return view('livewire.role-assignment', [
            'users' => $users
        ]);
    }

    public function updateItemsOrder($boxId, $orderedItemIds)
    {
        $box = collect($this->boxes)->firstWhere('id', $boxId);

        if ($box) {
            $box['items'] = collect($orderedItemIds)->map(function ($itemId) {
                return collect($this->items)->firstWhere('id', $itemId);
            })->toArray();

            $this->boxes = collect($this->boxes)->map(function ($b) use ($box) {
                return $b['id'] === $box['id'] ? $box : $b;
            })->toArray();
        }
    }
}
