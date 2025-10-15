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

    public function send(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );



        return $status == Password::RESET_LINK_SENT
            ? back()->with(['message' => __($status)])
            : back()->with(['error' => __($status)]);
    }
}
