<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Closure;
use Illuminate\Auth\Events\Verified;
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
            if ($request->user() && ! $request->user()->is_active) {
                abort(403, 'Účet je zablokovaný.');
            }

            return $next($request);
        });
    }

    public function notice(Request $request): RedirectResponse|View
    {
        if ($request->user() && $request->user()->hasVerifiedEmail()) {
            return to_route('calendar.index');
        }

        return view('users.verify-email');
    }

    public function verify(Request $request, string $id, string $hash): RedirectResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Neplatný overovací odkaz.');
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            event(new Verified($user));
        }

        return to_route('verification.verified');
    }

    public function verified(): View
    {
        return view('users.verified');
    }

    public function send(Request $request): RedirectResponse
    {
        if ($request->user()?->hasVerifiedEmail()) {
            return to_route('calendar.index');
        }

        $request->user()?->sendEmailVerificationNotification();

        return back()->with('message', 'Overovací e-mail odoslaný znova.');
    }

    public function resendGuest(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if ($user && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return back()->with('message', 'Overovací e-mail bol odoslaný. Skontroluj si schránku.');
    }
}
