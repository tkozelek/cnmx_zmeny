<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

/**
 * The pre-Laravel-11 bootstrap: this app still has app/Http/Kernel.php rather than a
 * configured bootstrap/app.php, so the test suite boots the same way.
 */
trait CreatesApplication
{
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
