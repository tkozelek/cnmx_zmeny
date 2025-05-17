<?php

namespace App\Providers;

use App\Http\Controllers\UserController;
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
        View::composer('components.layout', function ($view) {
            $userController = app(UserController::class);
            $newUserCount = $userController->getNewUserCount();
            $view->with('newUserCount', $newUserCount);
        });
    }
}
