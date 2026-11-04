<?php

namespace App\Http\ViewComposers;

use App\Models\Team;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\View;

/** Who the navigation is for: a verified user gets the app menu, a guest the login links. */
class LayoutComposer
{
    public function compose(View $view): void
    {
        $user = auth()->user();

        $view->with([
            'navUser' => $user?->hasVerifiedEmail() ? $user : null,
            'isGuest' => $user === null,
            'activeGuestLink' => match (true) {
                request()->routeIs('login', 'password.*') => 'login',
                request()->routeIs('register') => 'register',
                default => null,
            },
            'canManageTeam' => $user !== null && app()->bound(Team::class) && $user->can('viewSettings', app(Team::class)),
            // Resolved through the manifest, so the hashed file names never end up in a template.
            'fontPreloads' => [
                Vite::asset('node_modules/@fontsource-variable/inter/files/inter-latin-wght-normal.woff2'),
                Vite::asset('node_modules/@fontsource-variable/inter/files/inter-latin-ext-wght-normal.woff2'),
                Vite::asset('node_modules/@fortawesome/fontawesome-free/webfonts/fa-solid-900.woff2'),
            ],
            'operatorName' => config('privacy.operator.name'),
            'currentYear' => now()->year,
        ]);
    }
}
