<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Team;
use App\Models\User;
use App\Notifications\AddUserResetPassword;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Managing the people in one cinema.
 */
class UserController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', User::class);

        return view('admin.index', [
            'roles' => Role::cases(),
        ]);
    }

    /**
     * Create an account, attach it to this team already approved, and email a
     * set-your-password link. If the mail fails the whole thing is rolled back — a user who
     * never gets the link cannot log in and cannot be told why.
     */
    public function store(StoreUserRequest $request, Team $team): RedirectResponse
    {
        $this->authorize('create', User::class);

        $fields = $request->safe()->except('role');
        $fields['name'] = mb_convert_case($fields['name'], MB_CASE_TITLE, 'UTF-8');
        $fields['lastname'] = mb_convert_case($fields['lastname'], MB_CASE_TITLE, 'UTF-8');
        $fields['password'] = Hash::make(Str::random(32));

        $user = User::create($fields);
        $user->teams()->attach($team, ['approved_at' => now()]);
        $user->assignRole($request->string('role')->value());

        try {
            $user->notify(new AddUserResetPassword(Password::broker('add_user')->createToken($user)));
        } catch (\Throwable) {
            $user->teams()->detach();
            $user->delete();

            return back()->with(['error' => 'Nastala chyba, kontaktujte administrátora.']);
        }

        return back()->with(['message' => 'Používateľ úspešne vytvorený.']);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('admin.edit', [
            'user' => $user,
            'roles' => Role::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $user->update($request->safe()->except('role'));
        $user->syncRoles([$request->string('role')->value()]);

        return to_route('admin.users.index')->with(['message' => 'Úspešne zmenené.', 'edit' => 'yes']);
    }

    /**
     * Deactivate, never delete.
     *
     * `shifts.user_id` and `rates.user_id` are RESTRICT on purpose — payroll history must
     * survive — so a real delete would fail for anyone who has ever worked. Deactivating
     * matches what the schema is built for, and `is_active = false` is exactly what the
     * legacy "blocked" role meant.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->update(['is_active' => false]);

        return to_route('admin.users.index')->with(['message' => 'Účet deaktivovaný.']);
    }
}
