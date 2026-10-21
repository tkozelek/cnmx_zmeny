<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ChangePasswordRequest;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    public function update(ChangePasswordRequest $request): RedirectResponse
    {
        $request->user()->update(['password' => $request->string('new_password')->value()]);

        // Invalidate the other sessions this password could still be used on.
        auth()->logoutOtherDevices($request->string('new_password')->value());

        return to_route('settings.index')
            ->withFragment('heslo')
            ->with(['message' => 'Heslo bolo zmenené.']);
    }
}
