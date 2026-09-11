<?php

namespace App\Http\Controllers\Hours;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The worked-hours screen. The grid itself is filled by ShiftController's JSON endpoints.
 */
class HoursController extends Controller
{
    public function index(Request $request): View
    {
        return $this->hoursFor($request, $request->user());
    }

    /**
     * Manager view of somebody else's hours.
     *
     * The membership check is the tenant boundary: `{user}` is a plain route binding over a
     * table shared by every cinema, so the permission alone would authorise reading a stranger.
     */
    public function show(Request $request, User $user): View
    {
        $team = app(Team::class);

        abort_unless(
            $request->user()->id === $user->id
                || ($user->isMemberOf($team) && $request->user()->hasPermissionInTeam('user.view-any', $team)),
            403
        );

        return $this->hoursFor($request, $user);
    }

    private function hoursFor(Request $request, User $user): View
    {
        $team = app(Team::class);
        $canViewOtherUsers = $request->user()->hasPermissionInTeam('user.view-any', $team);

        return view('hours.index', [
            'user' => $user,
            'rates' => $user->rate,
            'users' => $canViewOtherUsers
                ? $team->users()->wherePivotNotNull('approved_at')->withCount('shifts')->orderBy('lastname')->get()
                : null,
        ]);
    }
}
