<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminUserEditRequest;
use App\Models\Role;
use App\Models\User;

class AdminUserEditController extends Controller
{
    public function update(AdminUserEditRequest $request, User $user)
    {
        $formFields = $request->validated();

        $user->update($formFields);

        return redirect('/admin/pouzivatelia')->with(['message' => 'Uspesne zmenene.', 'edit' => 'yes']);
    }

    public function edit(User $user)
    {
        $roles = Role::all();

        return view('admin.edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }
}
