<?php

namespace App\Http\ViewComposers;

use App\Models\User;
use Illuminate\View\View;

class UnverifiedUserComposerViewComposer
{
    public function compose(View $view)
    {
        if (! auth()->check()) {
            return;
        }
        $user = auth()->user();
        if (! $user->hasRole(config('constants.roles.admin'))) {
            return;
        }
        $newUserCount = User::where('id_role', config('constants.roles.neovereny'))->count();
        $view->with('newUserCount', $newUserCount);
    }
}
