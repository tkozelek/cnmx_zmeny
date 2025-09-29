<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRegistrationRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Str;

class UserController extends Controller
{
    public function changePasswordSave(Request $request)
    {
        $formFields = $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|confirmed|min:5',
        ], $this->messages());

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

    public function destroy(User $user)
    {
        $files = $user->files();

        foreach ($files as $file) {
            $this->fileService->deleteFile($file);
        }
        $user->days()->detach();
        $user->holidays()->delete();

        $user->delete();

        return to_route('admin.users.index')->with(['message' => 'Účet zmazaný.']);
    }
}
