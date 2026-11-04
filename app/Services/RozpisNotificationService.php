<?php

namespace App\Services;

use App\Enums\TeamFeature;
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
use Illuminate\Support\Facades\Cache;
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
        if (! $team->hasFeature(TeamFeature::Rozpis)) {
            return;
        }

        Notification::send($this->usersForWeek($team, $weekStart), new RozpisPublished($weekStart, $team->name));
    }

    /**
     * Called by the debounce job once the edit window has passed - see scheduleChangeNotification().
     * Also mails whoever an edit in the window touched, so somebody taken off the week hears about it.
     */
    public function notifyChanged(Team $team, CarbonImmutable $weekStart): void
    {
        $affectedUserIds = Cache::pull($this->affectedUsersKey($team->id, $weekStart), []);

        if (! $team->hasFeature(TeamFeature::Rozpis)
            || ! WeekLock::forWeek($team->id, $weekStart)?->isRozpisPublished()) {
            return;
        }

        Notification::send($this->usersForWeek($team, $weekStart, $affectedUserIds), new RozpisChanged($weekStart, $team->name));
    }

    /**
     * Debounced entry point for every Assignment/PositionSlot save or delete - see
     * NotifiesRozpisChange. No-ops for a week that was never published: nobody has seen a rozpis
     * yet, so there is nothing to tell them "changed".
     *
     * The job's own ShouldBeUnique lock is what collapses a burst of edits into one mail - this
     * method just needs to keep dispatching on every change and let the job sort it out.
     */
    /**
     * @param  list<int>  $affectedUserIds
     */
    public function scheduleChangeNotification(int $teamId, CarbonInterface $date, array $affectedUserIds = []): void
    {
        $team = Team::find($teamId);

        if (! $team?->hasFeature(TeamFeature::Rozpis)) {
            return;
        }

        $weekStart = $this->weeks->start($team, CarbonImmutable::parse($date));

        if (! WeekLock::forWeek($teamId, $weekStart)?->isRozpisPublished()) {
            return;
        }

        if ($affectedUserIds !== []) {
            $key = $this->affectedUsersKey($teamId, $weekStart);
            $ttl = now()->addMinutes(SendRozpisChangeNotification::DEBOUNCE_MINUTES * 4);

            Cache::put($key, array_values(array_unique([...Cache::get($key, []), ...$affectedUserIds])), $ttl);
        }

        SendRozpisChangeNotification::dispatch($teamId, $weekStart->toDateString())
            ->delay(now()->addMinutes(SendRozpisChangeNotification::DEBOUNCE_MINUTES));
    }

    private function affectedUsersKey(int $teamId, CarbonInterface $weekStart): string
    {
        return "rozpis-change:{$teamId}:{$weekStart->toDateString()}:users";
    }

    /**
     * Everyone on the week plus $alsoUserIds - limited to active, approved members of the team,
     * so a deactivated or removed person is never mailed about a cinema they can no longer open.
     *
     * @param  list<int>  $alsoUserIds
     * @return Collection<int, User>
     */
    private function usersForWeek(Team $team, CarbonImmutable $weekStart, array $alsoUserIds = []): Collection
    {
        [$from, $to] = $this->weeks->range($weekStart);

        $userIds = Assignment::query()
            ->withoutGlobalScope('team')
            ->where('team_id', $team->id)
            ->betweenDates($from, $to)
            ->distinct()
            ->pluck('user_id')
            ->merge($alsoUserIds)
            ->unique();

        return User::query()
            ->whereIn('id', $userIds)
            ->where('is_active', true)
            ->whereHas('teams', fn ($query) => $query->whereKey($team->id)->whereNotNull('team_user.approved_at'))
            ->get();
    }
}
