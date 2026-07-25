<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\PermissionRegistrar;

class RegisterController extends Controller
{
    public function index(): View
    {
        return view('users.register', [
            'teams' => Team::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    /**
     * Self-registration creates the account and a *pending* membership: no `approved_at`,
     * so `EnsureUserIsActive` keeps them out until an admin accepts them. That pending
     * state is what the legacy "neovereny" role used to mean.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $team = $this->resolveTeam($request->integer('team_id') ?: null);

        if (! $team) {
            return back()->with(['error' => 'Vyber si kino.'])->withInput();
        }

        $fields = $request->safe()->except('team_id');
        $fields['name'] = mb_convert_case($fields['name'], MB_CASE_TITLE, 'UTF-8');
        $fields['lastname'] = mb_convert_case($fields['lastname'], MB_CASE_TITLE, 'UTF-8');

        $user = User::create($fields);
        $user->teams()->attach($team, ['approved_at' => null]);

        // The role assignment is scoped by model_has_roles.team_id, which the registrar is
        // not holding during a guest request — so it is set explicitly here.
        app(PermissionRegistrar::class)->setPermissionsTeamId($team->getKey());
        $user->assignRole(Role::Employee->value);

        return to_route('welcome.index')->with('message', 'Účet vytvorený. Počkaj na schválenie.');
    }

    /** Skip the picker when there is only one cinema to join. */
    private function resolveTeam(?int $teamId): ?Team
    {
        $active = Team::where('is_active', true);

        if ($teamId) {
            return $active->find($teamId);
        }

        return $active->count() === 1 ? $active->first() : null;
    }
}
