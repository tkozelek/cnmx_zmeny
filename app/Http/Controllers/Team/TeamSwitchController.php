<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Move the user to another of their cinemas.
 *
 * `switchTeam()` refuses teams the user is not approved in, so a forged id in the URL
 * cannot be used to step across the tenant boundary.
 */
class TeamSwitchController extends Controller
{
    public function __invoke(Request $request, Team $team): RedirectResponse
    {
        if (! $request->user()->switchTeam($team)) {
            return back()->with(['error' => 'Do tohto kina nemáš prístup.']);
        }

        return redirect()->back()->with(['message' => 'Kino prepnuté na '.$team->name.'.']);
    }
}
