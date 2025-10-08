<?php

namespace App\Providers;

use App\Http\ViewComposers\UnverifiedUserComposerViewComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Use a view composer to pass the new user count to the layout view
        View::composer('layouts.layout', UnverifiedUserComposerViewComposer::class);
    }
}
