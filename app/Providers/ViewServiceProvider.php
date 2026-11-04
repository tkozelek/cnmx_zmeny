<?php

namespace App\Providers;

use App\Http\ViewComposers\ErrorPageComposer;
use App\Http\ViewComposers\LayoutComposer;
use App\Http\ViewComposers\PendingMemberCountComposer;
use App\Http\ViewComposers\PrivacyComposer;
use App\Http\ViewComposers\VerifyEmailComposer;
use App\Http\ViewComposers\WelcomeComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('layouts.layout', PendingMemberCountComposer::class);
        View::composer('layouts.layout', LayoutComposer::class);
        View::composer(['errors::minimal', 'errors::403', 'errors::429'], ErrorPageComposer::class);
        View::composer('welcome.welcome', WelcomeComposer::class);
        View::composer('privacy.index', PrivacyComposer::class);
        View::composer('users.verify-email', VerifyEmailComposer::class);
    }
}
