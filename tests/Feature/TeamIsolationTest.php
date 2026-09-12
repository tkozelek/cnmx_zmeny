<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Absence;
use App\Models\Assignment;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_switch_to_unassigned_team(): void
    {
        $teamA = $this->tenant();
        $teamB = Team::factory()->create(['name' => 'Kino B']);

        $user = $this->member($teamA);

        $response = $this->actingAs($user)->post(route('teams.switch', $teamB));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Do tohto kina nemáš prístup.');
        $this->assertEquals($teamA->id, $user->fresh()->current_team_id);
    }

    public function test_user_cannot_switch_to_unapproved_team(): void
    {
        $teamA = $this->tenant();
        $teamB = Team::factory()->create(['name' => 'Kino B']);

        $user = $this->member($teamA);
        $user->teams()->attach($teamB, ['approved_at' => null]);

        $response = $this->actingAs($user)->post(route('teams.switch', $teamB));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Do tohto kina nemáš prístup.');
        $this->assertEquals($teamA->id, $user->fresh()->current_team_id);
    }

    public function test_user_can_switch_to_approved_team(): void
    {
        $teamA = $this->tenant();
        $teamB = Team::factory()->create(['name' => 'Kino B']);

        $user = $this->member($teamA);
        $user->teams()->attach($teamB, ['approved_at' => now()]);

        $response = $this->actingAs($user)->post(route('teams.switch', $teamB));

        $response->assertRedirect();
        $this->assertEquals($teamB->id, $user->fresh()->current_team_id);
    }

    public function test_switching_team_preserves_current_url_location(): void
    {
        $teamA = $this->tenant();
        $teamB = Team::factory()->create(['name' => 'Kino B']);

        $admin = $this->member($teamA, Role::HeadManager);
        $admin->teams()->attach($teamB, ['approved_at' => now()]);

        $usersUrl = route('admin.users.index');

        $response = $this->actingAs($admin)
            ->from($usersUrl)
            ->post(route('teams.switch', $teamB));

        $response->assertRedirect($usersUrl);
        $this->assertEquals($teamB->id, $admin->fresh()->current_team_id);
    }

    /**
     * `User` is the one model here that is not team-owned - it is shared across cinemas through
     * `team_user`, so nothing scopes a route binding on it. Holding `user.update` in one cinema
     * used to authorise editing, role-changing or deactivating *any* account in the database by
     * guessing its id; UserPolicy now checks the target's membership as well as the actor's
     * permission.
     */
    public function test_a_manager_cannot_manage_a_user_from_another_cinema(): void
    {
        $teamA = $this->tenant();
        $manager = $this->member($teamA, Role::Manager);

        $teamB = Team::factory()->create(['name' => 'Kino B']);
        $outsider = $this->member($teamB);

        $this->actingAs($manager)
            ->get(route('admin.users.edit', $outsider))
            ->assertForbidden();

        $this->actingAs($manager)
            ->delete(route('admin.users.destroy', $outsider))
            ->assertForbidden();

        $this->assertTrue($outsider->fresh()->is_active, 'A stranger must not be deactivable from here.');
    }

    /** Same boundary on the read side: `user.view` is a permission every employee holds. */
    public function test_an_employee_cannot_read_a_profile_from_another_cinema(): void
    {
        $teamA = $this->tenant();
        $employee = $this->member($teamA);

        $teamB = Team::factory()->create(['name' => 'Kino B']);
        $outsider = $this->member($teamB);

        $this->actingAs($employee)
            ->get(route('profile.show', $outsider))
            ->assertForbidden();
    }

    public function test_resources_are_strictly_isolated_between_teams(): void
    {
        $teamA = $this->tenant();
        $teamB = Team::factory()->create(['name' => 'Kino B']);

        $userA = $this->member($teamA);
        $userB = $this->member($teamB);

        $date = '2026-08-01';

        Assignment::factory()->create([
            'team_id' => $teamA->id,
            'user_id' => $userA->id,
            'date' => $date,
        ]);

        Assignment::factory()->create([
            'team_id' => $teamB->id,
            'user_id' => $userB->id,
            'date' => $date,
        ]);

        Absence::factory()->create([
            'team_id' => $teamA->id,
            'user_id' => $userA->id,
            'date_from' => $date,
            'date_to' => $date,
        ]);

        Absence::factory()->create([
            'team_id' => $teamB->id,
            'user_id' => $userB->id,
            'date_from' => $date,
            'date_to' => $date,
        ]);

        $responseA = $this->actingAs($userA)->get(route('calendar.show', ['date' => $date]));
        $responseA->assertOk();
        $responseA->assertSee((string) $userA);
        $responseA->assertDontSee((string) $userB);

        $responseB = $this->actingAs($userB)->get(route('calendar.show', ['date' => $date]));
        $responseB->assertOk();
        $responseB->assertSee((string) $userB);
        $responseB->assertDontSee((string) $userA);
    }
}
