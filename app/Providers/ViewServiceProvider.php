<?php

namespace App\Providers;

use App\Http\ViewComposers\PendingMemberCountComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('layouts.layout', PendingMemberCountComposer::class);
    }
}
