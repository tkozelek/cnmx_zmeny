<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TeamFeature;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TeamSettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_team_update_permission_can_edit_settings(): void
    {
        $team = $this->tenant();
        Permission::findOrCreate('team.view', 'web');
        Permission::findOrCreate('team.update', 'web');

        $admin = $this->member($team, Role::HeadManager);
        app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
        $admin->givePermissionTo('team.view');
        $admin->givePermissionTo('team.update');

        $this->actingAs($admin)
            ->get(route('team.settings.edit'))
            ->assertOk()
            ->assertSee('Uložiť nastavenia');

        $this->actingAs($admin)
            ->put(route('team.settings.update'), [
                'name' => 'Kino Žilina Nové',
                'week_start_day' => 0,
                'week_lookahead' => 6,
                'absence_deadline_days' => 1,
                'stale_absence_deletion_days' => 45,
            ])
            ->assertRedirect()
            ->assertSessionHas('message');

        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'Kino Žilina Nové']);
        $this->assertDatabaseHas('team_settings', [
            'team_id' => $team->id,
            'stale_absence_deletion_days' => 45,
        ]);
    }

    public function test_a_cinema_with_rozpis_also_saves_fairness_settings(): void
    {
        $team = $this->tenant();
        Feature::for($team)->activate(TeamFeature::Rozpis);

        $this->actingAs($this->member($team, Role::HeadManager))
            ->put(route('team.settings.update'), [
                'name' => $team->name,
                'week_start_day' => 3,
                'week_lookahead' => 6,
                'absence_deadline_days' => 1,
                'stale_absence_deletion_days' => 45,
                'fairness_day_weights' => [1, 1, 1, 1, 1.5, 2, 2],
                'fairness_window_weeks' => 8,
                'holiday_weight' => 1.8,
            ])
            ->assertRedirect()
            ->assertSessionHas('message');

        $this->assertDatabaseHas('team_settings', [
            'team_id' => $team->id,
            'fairness_window_weeks' => 8,
            'holiday_weight' => 1.8,
        ]);

        $this->assertSame([1.0, 1.0, 1.0, 1.0, 1.5, 2.0, 2.0], $team->fresh()->fairnessDayWeights());
    }

    public function test_user_with_view_only_permission_sees_disabled_fields(): void
    {
        $team = $this->tenant();
        $permission = Permission::findOrCreate('team.view', 'web');
        Permission::findOrCreate('team.update', 'web');

        $employee = $this->member($team, Role::Employee);

        app(PermissionRegistrar::class)->setPermissionsTeamId($team->id);
        $employee->givePermissionTo($permission);

        $this->actingAs($employee)
            ->get(route('team.settings.edit'))
            ->assertOk()
            ->assertSee('Máš iba práva na zobrazenie nastavení')
            ->assertDontSee('Uložiť nastavenia');

        // Cannot update settings without team.update permission
        $this->actingAs($employee)
            ->put(route('team.settings.update'), [
                'name' => 'Forged Name',
                'week_start_day' => 0,
                'week_lookahead' => 6,
                'absence_deadline_days' => 1,
                'stale_absence_deletion_days' => 45,
            ])
            ->assertForbidden();
    }

    public function test_user_without_team_permissions_is_forbidden(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team, Role::Employee);

        $this->actingAs($employee)
            ->get(route('team.settings.edit'))
            ->assertForbidden();
    }

    public function test_the_week_start_cannot_move_while_the_current_week_is_locked(): void
    {
        $team = $this->tenant();
        $headManager = $this->member($team, Role::HeadManager);
        $team->weekLocks()->create([
            'week_start' => app(WeekService::class)->start($team, CarbonImmutable::now())->toDateString(),
            'locked_by' => $headManager->id,
        ]);

        $this->actingAs($headManager)
            ->put(route('team.settings.update'), [
                'name' => $team->name,
                'week_start_day' => ($team->weekStartDay() + 1) % 7,
                'week_lookahead' => 5,
                'absence_deadline_days' => 2,
                'stale_absence_deletion_days' => 7,
            ])
            ->assertSessionHasErrors('week_start_day');
    }
}
