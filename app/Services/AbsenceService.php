<?php

namespace App\Services;

use App\Enums\AbsenceStatus;
use App\Models\Absence;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Support\Collection;

/**
 * Reads over absences. Replaces the legacy HolidayService.
 *
 * "Cancelled" no longer exists as a column: an absence is ended by shortening `date_to`,
 * or deleted outright if it never started. So "past" is simply date_to < today.
 */
class AbsenceService
{
    /**
     * Absences still running or yet to start. Null user = everyone in the current team.
     *
     * @return Collection<int, Absence>
     */
    public function activeFor(?User $user = null): Collection
    {
        return Absence::with('user')
            ->active()
            ->when($user, fn ($query) => $query->where('user_id', $user->id))
            ->orderBy('date_from')
            ->get();
    }

    /**
     * @return Paginator<int, Absence>
     */
    public function pastFor(?User $user = null, int $perPage = 15): Paginator
    {
        return Absence::with('user')
            ->past()
            ->when($user, fn ($query) => $query->where('user_id', $user->id))
            ->orderByDesc('date_to')
            ->paginate($perPage);
    }

    /**
     * End an absence: shorten it to today if it is already running, or mark status as cancelled
     * if deactivated in advance so it remains stored in history as inactive/cancelled.
     */
    public function end(Absence $absence): void
    {
        if ($absence->date_from->isFuture()) {
            $absence->update([
                'status' => AbsenceStatus::Cancelled,
            ]);

            return;
        }

        $yesterday = now()->subDay()->startOfDay();
        $newDateTo = $absence->date_from->gt($yesterday)
            ? $absence->date_from
            : $yesterday;

        $absence->update([
            'date_to' => $newDateTo->toDateString(),
            'status' => AbsenceStatus::Cancelled,
        ]);
    }
}
