<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
        'new_password',
        'new_password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Both are on the framework's never-report list, so they are logged on the way to the
        // response instead. Returning nothing leaves the normal 403/429 page untouched.
        $this->renderable(function (AccessDeniedHttpException $e): void {
            Log::channel('security')->warning('Prístup zamietnutý (403).', ['reason' => $e->getMessage()]);
        });

        $this->renderable(function (ThrottleRequestsException $e): void {
            Log::channel('security')->warning('Príliš veľa požiadaviek (429).');
        });
    }
}
