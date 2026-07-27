<?php

namespace App\Livewire;

use App\Models\Assignment;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * One day of the week plan. Seven of these make up the calendar.
 */
class DayCard extends Component
{
    /** Y-m-d. The identity of the day; locked against tampering. */
    #[Locked]
    public string $date;

    #[Locked]
    public bool $locked = false;

    /** Preloaded assignments passed from parent page on initial load to eliminate N+1 queries. */
    public ?Collection $initialAssignments = null;

    public function signUp(): void
    {
        $team = app(Team::class);

        $this->authorize('create', [Assignment::class, $team, $this->day()]);

        Assignment::firstOrCreate(
            [
                'user_id' => auth()->id(),
                'date' => $this->date,
            ],
            [
                'note' => $this->sharedNote(),
                'created_by' => null,
            ],
        );

        unset($this->assignments, $this->mine);

        $this->dispatch('assignment-updated')->to(SignupSummary::class);
        $this->dispatch('toast', message: 'Deň zapísaný.');
    }

    public function withdraw(): void
    {
        if (! $this->mine) {
            return;
        }

        $this->remove($this->mine);
    }

    public function remove(Assignment $assignment): void
    {
        $this->authorize('delete', $assignment);

        $assignment->delete();

        unset($this->assignments, $this->mine);

        $this->dispatch('assignment-updated')->to(SignupSummary::class);
        $this->dispatch('toast', message: 'Deň odpísaný.', type: 'error');
    }

    /**
     * @return Collection<int, Assignment>
     */
    #[Computed]
    public function assignments(): Collection
    {
        if ($this->initialAssignments !== null) {
            $assignments = $this->initialAssignments;
            $this->initialAssignments = null;

            return $assignments->sortBy(fn (Assignment $a): string => (string) $a->user);
        }

        return Assignment::with(['user', 'position'])
            ->where('date', $this->date)
            ->get()
            ->sortBy(fn (Assignment $a): string => (string) $a->user);
    }

    #[Computed]
    public function mine(): ?Assignment
    {
        return $this->assignments->firstWhere('user_id', auth()->id());
    }

    #[Computed]
    public function isAdmin(): bool
    {
        $team = app(Team::class);

        return auth()->user()?->hasPermissionInTeam('assignment.delete', $team) ?? false;
    }

    #[Computed]
    public function canViewUsers(): bool
    {
        return auth()->user()?->can('viewAny', User::class) ?? false;
    }

    #[Computed]
    public function isToday(): bool
    {
        return $this->day()->isToday();
    }

    #[Computed]
    public function dayCarbon(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->date);
    }

    #[Computed]
    public function countLabel(): string
    {
        $count = $this->assignments->count();

        return match (true) {
            $count === 1 => 'zapísaný',
            $count >= 2 && $count <= 4 => 'zapísaní',
            default => 'zapísaných',
        };
    }

    public function canRemove(Assignment $assignment): bool
    {
        return ! $this->locked && ($assignment->user_id === auth()->id() || $this->isAdmin);
    }

    public function render()
    {
        return view('livewire.day-card');
    }

    private function sharedNote(): ?string
    {
        $note = trim((string) session(ExtraNote::SESSION_KEY, ''));

        return $note === '' ? null : mb_substr($note, 0, ExtraNote::MAX_LENGTH);
    }

    private function day(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->date)->startOfDay();
    }
}
