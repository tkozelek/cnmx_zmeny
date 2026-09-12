<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('nastavenia.editPassword');
    }

    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->string('new_password')->value()]);

        // Invalidate the other sessions this password could still be used on.
        auth()->logoutOtherDevices($request->string('new_password')->value());

        return back()->with(['message' => 'Heslo zmenené.']);
    }
}
