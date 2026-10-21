<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TeamFeature;
use App\Filament\Platform\Resources\Teams\Pages\EditTeam;
use App\Filament\Platform\Resources\Teams\RelationManagers\MembersRelationManager;
use App\Filament\Platform\Resources\Users\Pages\EditUser;
use App\Models\User;
use App\Services\SuperAdminSession;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('platform');
    }

    public function test_only_super_admins_get_into_the_panel_and_only_after_setting_up_totp(): void
    {
        $team = $this->tenant();

        $this->actingAs($this->member($team, Role::HeadManager), SuperAdminSession::GUARD)
            ->get('/platform')
            ->assertForbidden();

        $withoutTotp = User::factory()->create()->forceFill(['is_super_admin' => true]);
        $withoutTotp->save();

        $this->actingAs($withoutTotp, SuperAdminSession::GUARD)
            ->get('/platform/teams')
            ->assertRedirect('/platform/multi-factor-authentication/set-up');

        $this->actingAs(User::factory()->superAdmin()->create(), SuperAdminSession::GUARD)
            ->get('/platform/teams')
            ->assertOk()
            ->assertSee($team->name);
    }

    public function test_a_module_is_switched_on_per_cinema_and_the_change_is_audited(): void
    {
        $team = $this->tenant();
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin, SuperAdminSession::GUARD)
            ->test(EditTeam::class, ['record' => $team->getRouteKey()])
            ->fillForm(['features.Rozpis' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($team->hasFeature(TeamFeature::Rozpis));
        $this->assertFalse($team->hasFeature(TeamFeature::RozpisAi));
        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'platform',
            'event' => 'feature.activated',
            'causer_id' => $superAdmin->id,
            'subject_id' => $team->id,
        ]);
    }

    public function test_rozpis_settings_are_editable_once_the_module_is_on(): void
    {
        $team = $this->tenant();
        Feature::for($team)->activate(TeamFeature::Rozpis);

        Livewire::actingAs(User::factory()->superAdmin()->create(), SuperAdminSession::GUARD)
            ->test(EditTeam::class, ['record' => $team->getRouteKey()])
            ->fillForm(['settings.fairness_day_weights' => [1, 1, 1, 1, 2.5, 0.8, 0.8], 'settings.quick_times' => ['13:00']])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = $team->settings()->first();
        $this->assertSame(2.5, $settings->fairness_day_weights[4]);
        $this->assertSame(['13:00'], $settings->quick_times);
    }

    public function test_granting_super_admin_needs_the_acting_admins_password(): void
    {
        $this->tenant();
        $superAdmin = User::factory()->superAdmin()->create();
        $target = User::factory()->create();

        Livewire::actingAs($superAdmin, SuperAdminSession::GUARD)
            ->test(EditUser::class, ['record' => $target->getRouteKey()])
            ->callAction('grantSuperAdmin', data: ['current_password' => 'wrong'])
            ->assertHasActionErrors(['current_password']);
        $this->assertFalse($target->fresh()->is_super_admin);

        Livewire::actingAs($superAdmin, SuperAdminSession::GUARD)
            ->test(EditUser::class, ['record' => $target->getRouteKey()])
            ->callAction('grantSuperAdmin', data: ['current_password' => 'password']);
        $this->assertTrue($target->fresh()->is_super_admin);
    }

    public function test_a_super_admin_cannot_revoke_themselves(): void
    {
        $this->tenant();
        $superAdmin = User::factory()->superAdmin()->create();
        User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin, SuperAdminSession::GUARD)
            ->test(EditUser::class, ['record' => $superAdmin->getRouteKey()])
            ->callAction('revokeSuperAdmin', data: ['current_password' => 'password'])
            ->assertNotified('Sám sebe super admina odobrať nemôžeš.');

        $this->assertTrue($superAdmin->fresh()->is_super_admin);
    }

    /** Handing over a whole cinema is guarded like granting super admin. */
    public function test_making_someone_head_manager_needs_the_acting_admins_password(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);
        $superAdmin = User::factory()->superAdmin()->create();

        Livewire::actingAs($superAdmin, SuperAdminSession::GUARD)
            ->test(MembersRelationManager::class, ['ownerRecord' => $team, 'pageClass' => EditTeam::class])
            ->callTableAction('changeRole', $employee, data: ['role' => Role::HeadManager->value, 'current_password' => 'wrong'])
            ->assertHasTableActionErrors(['current_password']);

        setPermissionsTeamId($team->id);
        $this->assertFalse($employee->fresh()->hasRole(Role::HeadManager->value));

        Livewire::actingAs($superAdmin, SuperAdminSession::GUARD)
            ->test(MembersRelationManager::class, ['ownerRecord' => $team, 'pageClass' => EditTeam::class])
            ->callTableAction('changeRole', $employee, data: ['role' => Role::HeadManager->value, 'current_password' => 'password']);

        setPermissionsTeamId($team->id);
        $this->assertTrue($employee->fresh()->hasRole(Role::HeadManager->value));
    }

    public function test_the_last_head_manager_cannot_be_demoted_from_the_panel(): void
    {
        $team = $this->tenant();
        $headManager = $this->member($team, Role::HeadManager);

        Livewire::actingAs(User::factory()->superAdmin()->create(), SuperAdminSession::GUARD)
            ->test(MembersRelationManager::class, ['ownerRecord' => $team, 'pageClass' => EditTeam::class])
            ->callTableAction('changeRole', $headManager, data: ['role' => Role::Employee->value])
            ->assertNotified('Toto je posledný hlavný manažér kina – najprv vymenuj ďalšieho.');

        setPermissionsTeamId($team->id);
        $this->assertTrue($headManager->fresh()->hasRole(Role::HeadManager->value));
    }

    public function test_the_cli_grants_the_role_to_an_existing_verified_account(): void
    {
        $user = User::factory()->create();

        $this->artisan('app:super-admin', ['email' => $user->email])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_super_admin);

        $this->artisan('app:super-admin', ['email' => 'nobody@example.com'])->assertFailed();
    }
}
