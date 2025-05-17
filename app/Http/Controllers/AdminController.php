<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;

class AdminController extends Controller
{
    public function index()
    {
        $roles = Role::all();

        return view('admin.index', [
            'roles' => $roles,
        ]);
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
