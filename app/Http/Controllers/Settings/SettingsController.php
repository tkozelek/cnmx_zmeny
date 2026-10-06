<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class SettingsController extends Controller
{
    /**
     * The old two-button "Nastavenia" page. Nothing links to it any more and the profile offers
     * the same two actions (absences, change password), so an old bookmark lands there.
     */
    public function index(): RedirectResponse
    {
        return to_route('profile.index');
    }
}
