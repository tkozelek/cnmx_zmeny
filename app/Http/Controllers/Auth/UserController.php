<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function changePasswordSave(Request $request)
    {
        $formFields = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|confirmed|min:5',
        ], config('constants.messages'));

        $user = auth()->user();

        if (! Hash::check($request->get('current_password'), $user->password)) {
            return back()->with('message', 'Zle aktuálne heslo.');
        }

        $user->password = Hash::make($formFields['new_password']);
        $user->save();

        return back()->with('message', 'Heslo bolo úspešne zmenené.');
    }

    public function logout(Request $request)
    {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('message', 'Boli ste úspešné odhlásený.');
    }
}
