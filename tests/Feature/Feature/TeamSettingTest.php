<?php

namespace Tests\Feature;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $admin = $this->member($team, Role::Admin);
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
                'fairness_day_weights' => [1, 1, 1, 1, 1.5, 2, 2],
                'fairness_window_weeks' => 8,
            ])
            ->assertRedirect()
            ->assertSessionHas('message');

        $this->assertDatabaseHas('teams', ['id' => $team->id, 'name' => 'Kino Žilina Nové']);
        $this->assertDatabaseHas('team_settings', [
            'team_id' => $team->id,
            'stale_absence_deletion_days' => 45,
            'fairness_window_weeks' => 8,
        ]);

        // Stored as numbers, not the strings a form posts - FairnessService indexes straight
        // into this array.
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
            ->assertSee('Máte iba práva na zobrazenie nastavení')
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
}
