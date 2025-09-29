<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function index() {
        return view('users.login');
    }

    public function authenticate(LoginRequest $request)
    {
        $remember = $request->has('remember');
        $formFields = $request->validated();

        if (auth()->attempt($formFields, $remember)) {
            $request->session()->regenerate();

            return redirect('/')->with('message', 'Úspešné prihlásený.');
        }

        return back()->with(['error' => 'Nesprávne údajé.'])->onlyInput('email');
    }
}
