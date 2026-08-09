<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Assignment;
use App\Models\Team;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Signing up for a day, coming off it again, and the lock that stops both - over plain
 * POST/DELETE.
 *
 * The calendar UI drives `App\Livewire\DayCard` instead (see DayCardTest), so these routes are
 * currently a no-JavaScript path nothing links to. They are kept because they are the only
 * place an admin can sign *somebody else* up, which the day card does not offer.
 */
class AssignmentSignupTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_employee_can_sign_up_for_a_day(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $date = CarbonImmutable::now()->addDays(3)->toDateString();

        $this->actingAs($user)
            ->post(route('assignments.store'), ['date' => $date])
            ->assertRedirect();

        $this->assertDatabaseHas('assignments', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'position_id' => null,
            'date' => $date,
            // Null created_by is what marks this as self-signup rather than an admin placing them.
            'created_by' => null,
        ]);
    }

    public function test_an_employee_can_remove_their_own_signup(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $assignment = Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->delete(route('assignments.destroy', $assignment))
            ->assertRedirect();

        $this->assertModelMissing($assignment);
    }

    public function test_an_employee_cannot_sign_up_for_a_locked_week(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $date = CarbonImmutable::now()->addDays(3);

        $team->weekLocks()->create(['week_start' => $this->weekStart($date)]);

        $this->actingAs($user)
            ->post(route('assignments.store'), [
                'date' => $date->toDateString(),
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_an_employee_cannot_remove_someone_elses_signup(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $colleague = $this->member($team);

        $assignment = Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $colleague->id,
        ]);

        $this->actingAs($user)
            ->delete(route('assignments.destroy', $assignment))
            ->assertForbidden();

        $this->assertModelExists($assignment);
    }

    public function test_an_admin_can_still_sign_someone_up_in_a_locked_week(): void
    {
        $team = $this->tenant();
        $admin = $this->member($team, Role::HeadManager);
        $employee = $this->member($team);
        $date = CarbonImmutable::now()->addDays(3);

        $team->weekLocks()->create(['week_start' => $this->weekStart($date)]);

        $this->actingAs($admin)
            ->post(route('assignments.store'), [
                'date' => $date->toDateString(),
                'user_id' => $employee->id,
            ])
            ->assertRedirect();

        // created_by set: an admin placed them, they did not sign themselves up.
        $this->assertDatabaseHas('assignments', [
            'user_id' => $employee->id,
            'created_by' => $admin->id,
        ]);
    }

    private function weekStart(CarbonImmutable $date): string
    {
        return app(WeekService::class)
            ->start(app(Team::class), $date)
            ->toDateString();
    }
}
