<?php

namespace App\Http\ViewComposers;

use App\Models\Team;
use Illuminate\View\View;

/**
 * Puts the "N people waiting to be approved" badge count into the layout, for admins.
 *
 * Pending means a `team_user` row with no `approved_at` - the state that used to be the
 * "neovereny" role.
 *
 * ponytail: not cached. The legacy 15-minute cache key had to be invalidated from three
 * places and still went stale; a COUNT over one indexed pivot per admin page view is
 * cheaper than that was to maintain.
 */
class PendingMemberCountComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        if (! app()->bound(Team::class) || ! $user?->hasPermissionInTeam('user.approve', app(Team::class))) {
            return;
        }

        $view->with('newUserCount', app(Team::class)->pendingUsers()->count());
    }
}
