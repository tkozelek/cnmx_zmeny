<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function index () {
        $roles = Role::all();
        return view('profile.index', [
            'roles' => $roles
        ]);
    }

    public function edit(User $user) {
        $roles = Role::all();
        return view('profile.edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }


}
