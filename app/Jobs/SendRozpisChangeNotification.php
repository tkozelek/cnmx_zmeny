<?php

namespace App\Jobs;

use App\Models\Team;
use App\Services\RozpisNotificationService;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * One "rozpis changed" mail per (team, week), however many edits happened in between.
 *
 * ShouldBeUnique drops every re-dispatch that lands while one is already pending for the same
 * week, so a manager's whole editing session collapses into the single mail scheduled by the
 * first change - see RozpisNotificationService::scheduleChangeNotification(), which dispatches
 * this with a matching delay.
 */
class SendRozpisChangeNotification implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** How long after the first change the mail goes out - also the window later edits get absorbed into. */
    public const int DEBOUNCE_MINUTES = 30;

    /** Safety cap on the uniqueness lock, in case a job never runs (e.g. queue paused). */
    public int $uniqueFor = self::DEBOUNCE_MINUTES * 60;

    public function __construct(private readonly int $teamId, private readonly string $weekStart) {}

    public function uniqueId(): string
    {
        return "{$this->teamId}:{$this->weekStart}";
    }

    public function handle(RozpisNotificationService $notifications): void
    {
        $team = Team::find($this->teamId);

        if (! $team) {
            return;
        }

        $notifications->notifyChanged($team, CarbonImmutable::parse($this->weekStart));
    }
}
