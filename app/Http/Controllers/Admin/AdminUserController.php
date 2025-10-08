<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminCreateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AddUserResetPassword;
use App\Services\FileService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Str;

class AdminUserController extends Controller
{
    public function __construct(private readonly FileService $fileService) {}

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
            $token = Password::broker('add_user')->createToken($user);
            $user->notify(new AddUserResetPassword($token));
        } catch (\Exception $e) {
            $user->delete();

            return back()->with(['error' => 'Nastala chyba, kontaktujte administrátora.']);
        }

        return back()->with(['message' => 'Pouzivatel uspesne vytvoreny']);
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
