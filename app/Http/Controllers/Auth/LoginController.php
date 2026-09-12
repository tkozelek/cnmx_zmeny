<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function index(): View
    {
        return view('users.login');
    }

    /**
     * Only email and password are checked here.
     *
     * `is_active` deliberately is *not* folded into the credentials: doing so makes a blocked
     * account indistinguishable from a wrong password, and leaves `EnsureUserIsActive`'s
     * accurate message ("Účet je zablokovaný.", "Ešte si nebol/a overený…") unreachable. That
     * middleware runs on the very next request and logs them straight back out, so there is
     * one place deciding who may be in and one accurate message.
     */
    public function authenticate(LoginRequest $request): RedirectResponse
    {
        if (! auth()->attempt($request->validated(), $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'Nesprávny email alebo heslo.'])
                ->onlyInput('email');
        }

        $user = $request->user();

        if (! $user->hasVerifiedEmail()) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('unverified_email', $user->email)
                ->with('show_unverified_modal', true);
        }

        $request->session()->regenerate();

        // Login is the only moment the plaintext exists, so it is the only moment an old hash can
        // be upgraded. Accounts still on the previous cost factor move over as people come back,
        // and raising BCRYPT_ROUNDS again later needs no migration - just this.
        if (Hash::needsRehash($user->password)) {
            $user->forceFill(['password' => $request->string('password')->value()])->save();
        }

        return to_route('calendar.index')->with('message', 'Úspešne prihlásený.');
    }
}
