<?php

namespace App\View\Components;

use App\Models\Team;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\Component;

class TeamSwitcher extends Component
{
    /** @var Collection<int, Team> */
    public Collection $teams;

    public ?Team $currentTeam;

    public function __construct()
    {
        $user = auth()->user();

        $this->teams = $user?->accessibleTeams() ?? new Collection;
        $this->currentTeam = $user?->currentTeam ?? $this->teams->first();
    }

    public function shouldRender(): bool
    {
        return $this->teams->count() > 1;
    }

    public function render(): View
    {
        return view('components.team-switcher');
    }
}
