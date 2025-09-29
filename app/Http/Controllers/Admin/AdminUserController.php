<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCreateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index()
    {
        $roles = Role::all();

        return view('admin.index', [
            'roles' => $roles,
        ]);
    }

    public function store(AdminCreateUserRequest $request)
    {
        $formFields = $request->validated();
        $formFields['password'] = Hash::make(Str::random(12));

        $formFields['name'] = mb_convert_case($formFields['name'], MB_CASE_TITLE, 'UTF-8');
        $formFields['lastname'] = mb_convert_case($formFields['lastname'], MB_CASE_TITLE, 'UTF-8');

        $user = User::create($formFields);
        try {
            $status = Password::broker('add_user')->sendResetLink(['email' => $formFields['email']]);
        } catch (\Exception $e) {
            $user->delete();
            return back()->with(['error' => 'Nastala chyba, kontaktujte administrátora.']);
        }

        if ($status != Password::RESET_LINK_SENT) {
            return back()->with(['error' => 'Nastala chyba, kontaktujte administrátora.']);
        }

        return back()->with(['message' => 'Pouzivatel uspesne vytvoreny']);
    }
}
