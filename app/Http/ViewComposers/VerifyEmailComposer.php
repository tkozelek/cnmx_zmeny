<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;

class VerifyEmailComposer
{
    public function compose(View $view): void
    {
        $view->with('unverifiedEmail', auth()->user()->email ?? session('unverified_email', 'tvoj e-mail'));
    }
}
