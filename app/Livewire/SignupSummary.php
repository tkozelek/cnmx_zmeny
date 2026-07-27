<?php

namespace App\Livewire;

use App\Models\Assignment;
use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class SignupSummary extends Component
{
    #[Locked]
    public string $weekStart;

    #[On('assignment-updated')]
    public function refreshSummary(): void
    {
        unset($this->signupCounts);
    }

    /**
     * @return Collection<int, object>
     */
    #[Computed]
    public function signupCounts(): Collection
    {
        $team = app(Team::class);

        if (! auth()->user()?->hasPermissionInTeam('user.view-any', $team)) {
            return collect();
        }

        $from = CarbonImmutable::parse($this->weekStart);
        $to = $from->addDays(6);

        return Assignment::betweenDates($from, $to)
            ->join('users', 'users.id', '=', 'assignments.user_id')
            ->groupBy('users.id', 'users.name', 'users.lastname')
            ->orderByDesc('count')
            ->get([
                'users.id as user_id',
                'users.name',
                'users.lastname',
                DB::raw('COUNT(DISTINCT assignments.date) as count'),
            ]);
    }

    public function render()
    {
        return view('livewire.signup-summary');
    }
}
