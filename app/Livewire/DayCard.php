<?php

namespace App\Livewire;

use App\Models\Assignment;
use App\Models\Team;
use App\Models\User;
use App\Models\WeekLock;
use App\Services\SlovakHolidays;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
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
    #[Locked]
    public ?Collection $initialAssignments = null;

    /**
     * Managers only: active brigádnici with no absence on this day, user id => name. Empty for
     * everyone else. See CalendarService::available().
     *
     * @var array<int, string>
     */
    #[Locked]
    public array $available = [];

    public function signUp(): void
    {
        $team = app(Team::class);

        if (! $this->allowed(Gate::inspect('create', [Assignment::class, $team, $this->day()]))) {
            return;
        }

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
        $this->dispatch('toast', message: 'Zapísali ste sa na '.$this->dayCarbon->format('d.m.'), type: 'success');
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
        if (! $this->allowed(Gate::inspect('delete', $assignment))) {
            return;
        }

        $isOwn = $assignment->user_id === auth()->id();

        $assignment->delete();

        unset($this->assignments, $this->mine);

        $this->dispatch('assignment-updated')->to(SignupSummary::class);

        $day = $this->dayCarbon->format('d.m.');

        $this->dispatch('toast', type: 'info', message: $isOwn
            ? "Odpísali ste sa z {$day}"
            : "Zápis zrušený: {$assignment->user}, {$day}");
    }

    /**
     * A refusal the card can explain (an absence, a week locked after the page loaded) becomes a
     * toast instead of Livewire's full-page 403 modal. One without a message is tampering - a
     * request the UI never offers - and still throws.
     */
    private function allowed(Response $response): bool
    {
        if ($response->allowed()) {
            return true;
        }

        if ($response->message() === null) {
            $response->authorize();
        }

        $team = app(Team::class);
        $this->locked = WeekLock::locked($team->getKey(), app(WeekService::class)->start($team, $this->day()));

        $this->dispatch('toast', message: $response->message(), type: 'error');

        return false;
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

            return $this->sortedByName($assignments);
        }

        return $this->sortedByName(Assignment::with('user')->where('date', $this->date)->get());
    }

    /**
     * Slovak alphabetical order - a byte sort puts "Ďurčová" after "Zeman".
     *
     * @param  Collection<int, Assignment>  $assignments
     * @return Collection<int, Assignment>
     */
    private function sortedByName(Collection $assignments): Collection
    {
        // ponytail: falls back to a byte sort on a server without ext-intl.
        $compare = class_exists(\Collator::class) ? (new \Collator('sk_SK'))->compare(...) : strcmp(...);

        return $assignments->sort(fn (Assignment $a, Assignment $b): int => $compare((string) $a->user, (string) $b->user));
    }

    /**
     * Who could still be drawn for this day: available and not signed up yet.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function drawable(): array
    {
        return array_diff_key($this->available, array_flip($this->assignments->pluck('user_id')->all()));
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
    public function isHoliday(): bool
    {
        return SlovakHolidays::isHoliday($this->dayCarbon);
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

    /** The × next to a name: a manager taking somebody else off the day. Your own row has the big button. */
    public function canRemove(Assignment $assignment): bool
    {
        return ! $this->locked && $this->isAdmin && $assignment->user_id !== auth()->id();
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
