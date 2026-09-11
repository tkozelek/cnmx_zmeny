<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordRequestController extends Controller
{
    public function index()
    {
        return view('passwordreset.index');
    }

    /**
     * One answer whatever happened, so the form cannot be used to test whether an address has
     * an account here. The only outcome worth distinguishing is being rate-limited, which the
     * person can act on; "no such user" is exactly what an attacker wants to hear.
     */
    public function send(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->with(['error' => __($status)]);
        }

        return back()->with([
            'message' => 'Ak k tejto adrese existuje účet, poslali sme na ňu odkaz na obnovu hesla.',
        ]);
    }
}
