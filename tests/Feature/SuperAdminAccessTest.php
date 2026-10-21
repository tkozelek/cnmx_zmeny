<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\DayCard;
use App\Livewire\UsersDataTable;
use App\Models\Team;
use App\Models\User;
use App\Services\SuperAdminSession;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SuperAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_super_admin_signed_in_to_the_platform_acts_in_any_cinema_with_full_powers(): void
    {
        $this->tenant();
        $other = Team::factory()->create();
        $superAdmin = $this->signedInSuperAdmin();

        $this->get(route('admin.users.index'))->assertOk();

        $this->post(route('teams.switch', $other))->assertSessionHasNoErrors();
        $this->assertSame($other->id, $superAdmin->fresh()->current_team_id);

        $this->post(route('weeks.lock', ['date' => CarbonImmutable::now()->addDays(3)->toDateString()]))->assertRedirect();
        $this->assertDatabaseHas('week_locks', ['team_id' => $other->id]);
    }

    public function test_the_flag_alone_grants_nothing_without_the_platform_session(): void
    {
        $this->tenant();
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->get(route('calendar.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_a_super_admin_never_lands_on_the_plan_or_in_the_people_lists(): void
    {
        $team = $this->tenant();
        $this->member($team, Role::HeadManager);
        $superAdmin = $this->signedInSuperAdmin($team);

        Livewire::test(DayCard::class, ['date' => CarbonImmutable::now()->addDays(3)->toDateString(), 'locked' => false])
            ->call('signUp');
        $this->assertDatabaseMissing('assignments', ['user_id' => $superAdmin->id]);

        $this->post(route('absences.store'), [
            'date_from' => CarbonImmutable::now()->addDays(10)->toDateString(),
            'date_to' => CarbonImmutable::now()->addDays(10)->toDateString(),
            'reason' => 'x',
        ])->assertForbidden();

        $this->assertFalse(Livewire::test(UsersDataTable::class)->instance()->builder()->whereKey($superAdmin->id)->exists());
    }

    private function signedInSuperAdmin(?Team $team = null): User
    {
        $superAdmin = User::factory()->superAdmin()->create(['current_team_id' => $team?->id]);

        $this->actingAs($superAdmin, SuperAdminSession::GUARD)->actingAs($superAdmin);

        return $superAdmin;
    }
}
