<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * These routes sit on plain `auth`, not on the `tenant` group, because a user who has not
 * confirmed their address has to be able to reach them - EnsureUserIsActive is what sends them
 * here, so guarding them with it would loop.
 *
 * That leaves `is_active` unchecked, which is the one thing from that middleware still worth
 * enforcing: a blocked account could otherwise authenticate and use these endpoints. So it is
 * checked here instead.
 */
class EmailVerificationController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, Closure $next) {
            abort_unless($request->user()?->is_active, 403, 'Účet je zablokovaný.');

            return $next($request);
        });
    }

    public function notice(Request $request): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
            ? to_route('calendar.index')
            : view('users.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->markEmailAsVerified();

            event(new Verified($request->user()));
        }

        return to_route('login')->with('message', 'E-mail overený. Vedúci ťa teraz môže schváliť.');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return to_route('calendar.index');
        }

        $request->user()->sendEmailVerificationNotification();

        return back()->with('message', 'Overovací e-mail odoslaný znova.');
    }
}
