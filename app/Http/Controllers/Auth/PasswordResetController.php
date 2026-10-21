<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordResetController extends Controller
{
    public function show($token, Request $request)
    {
        return view('passwordreset.passwordform', [
            'token' => $token,
            'email' => $request->email ?? null,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            // Same strength as registration and the change-password form. This path used to
            // accept six characters, which made it the cheapest way to weaken an account.
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        // A forgot-password link (60 min) and a manager's invite (24h) live in separate token
        // tables, so whichever one holds this token decides how old it may be.
        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');
        $setPassword = function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
            ])->setRememberToken(Str::random(60));

            $user->save();

            event(new PasswordReset($user));
        };

        $status = Password::broker('users')->reset($credentials, $setPassword);

        if ($status === Password::INVALID_TOKEN) {
            $status = Password::broker('add_user')->reset($credentials, $setPassword);
        }

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('message', __($status))
            : back()->with(['message' => [__($status)]]);
    }
}
