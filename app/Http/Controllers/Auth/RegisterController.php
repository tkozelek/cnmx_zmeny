<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRegistrationRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function index()
    {
        return view('users.register');
    }

    public function store(UserRegistrationRequest $request)
    {
        $validatedData = $request->validated();
        // hash
        $validatedData['password'] = Hash::make($validatedData['password']);

        $validatedData['name'] = mb_convert_case($validatedData['name'], MB_CASE_TITLE, 'UTF-8');
        $validatedData['lastname'] = mb_convert_case($validatedData['lastname'], MB_CASE_TITLE, 'UTF-8');

        User::create($validatedData);

        // login
        // auth()->login($user);

        return to_route('welcome.index')->with('message', 'Účet vytvorený. Počkaj na schválenie.');
    }
}
