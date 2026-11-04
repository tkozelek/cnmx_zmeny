<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordRequestController extends Controller
{
    public function index(): View
    {
        return view('passwordreset.index');
    }

    /**
     * One answer whatever happened, so the form cannot be used to test whether an address has
     * an account here. Not even "throttled": the broker only throttles an address that exists,
     * so asking twice in a minute would tell. The first link is still valid anyway.
     */
    public function send(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);

        Password::sendResetLink($request->only('email'));

        return back()->with([
            'message' => 'Ak k tejto adrese existuje účet, poslali sme na ňu odkaz na obnovu hesla.',
        ]);
    }
}
