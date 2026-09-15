<?php

namespace App\Services;

use App\Jobs\SendRozpisChangeNotification;
use App\Models\Assignment;
use App\Models\Team;
use App\Models\User;
use App\Models\WeekLock;
use App\Notifications\RozpisChanged;
use App\Notifications\RozpisPublished;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class RozpisNotificationService
{
    public function __construct(private readonly WeekService $weeks) {}

    /**
     * Mails everyone with at least one assignment that week - queued, so publishing itself
     * stays fast regardless of how many people are on the schedule.
     */
    public function notifyPublished(Team $team, CarbonImmutable $weekStart): void
    {
        Notification::send($this->usersForWeek($team, $weekStart), new RozpisPublished($weekStart));
    }

    /** Called by the debounce job once the edit window has passed - see scheduleChangeNotification(). */
    public function notifyChanged(Team $team, CarbonImmutable $weekStart): void
    {
        Notification::send($this->usersForWeek($team, $weekStart), new RozpisChanged($weekStart));
    }

    /**
     * Debounced entry point for every Assignment/PositionSlot save or delete - see
     * NotifiesRozpisChange. No-ops for a week that was never published: nobody has seen a rozpis
     * yet, so there is nothing to tell them "changed".
     *
     * The job's own ShouldBeUnique lock is what collapses a burst of edits into one mail - this
     * method just needs to keep dispatching on every change and let the job sort it out.
     */
    public function scheduleChangeNotification(int $teamId, CarbonInterface $date): void
    {
        $team = Team::find($teamId);

        if (! $team) {
            return;
        }

        $weekStart = $this->weeks->start($team, CarbonImmutable::parse($date));

        if (! WeekLock::forWeek($teamId, $weekStart)?->isRozpisPublished()) {
            return;
        }

        SendRozpisChangeNotification::dispatch($teamId, $weekStart->toDateString())
            ->delay(now()->addMinutes(SendRozpisChangeNotification::DEBOUNCE_MINUTES));
    }

    /**
     * @return Collection<int, User>
     */
    private function usersForWeek(Team $team, CarbonImmutable $weekStart): Collection
    {
        [$from, $to] = $this->weeks->range($weekStart);

        $userIds = Assignment::query()
            ->where('team_id', $team->id)
            ->betweenDates($from, $to)
            ->distinct()
            ->pluck('user_id');

        return User::whereIn('id', $userIds)->get();
    }
}
