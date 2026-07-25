<?php

namespace Tests;

use App\Enums\Role;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role as SpatieRole;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // The rate limiter keeps its counters in the cache, and the array store survives
        // between tests in one process. Without this, a throttled route (login, hours, rates)
        // starts each test with the previous test's attempt count and eventually returns 429
        // for reasons that have nothing to do with the test — which reads as a real failure
        // and only shows up when the suite runs in a particular order.
        Cache::flush();
    }

    /**
     * A configured cinema with the three roles available and the tenant context set.
     *
     * Outside a request nothing calls SetCurrentTeam, so the registrar has to be primed by
     * hand — `BelongsToTeam` scopes every query off it, and without this every team-owned
     * query would run unscoped and quietly pass tests it should fail.
     */
    protected function tenant(): Team
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->forgetCachedPermissions();

        // Role rows are global; the team lives on the assignment, not the role.
        $registrar->setPermissionsTeamId(null);

        foreach (Role::cases() as $role) {
            SpatieRole::findOrCreate($role->value, 'web');
        }

        $team = Team::factory()->create();

        $registrar->setPermissionsTeamId($team->id);
        app()->instance(Team::class, $team);

        return $team;
    }

    /** An approved member of $team holding $role. */
    protected function member(Team $team, Role $role = Role::Employee): User
    {
        return User::factory()->memberOf($team, $role->value)->create();
    }
}
