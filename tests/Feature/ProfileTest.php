<?php

namespace Tests\Feature;

use App\Enums\AbsenceStatus;
use App\Enums\Role;
use App\Models\Absence;
use App\Models\Assignment;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_profile(): void
    {
        $team = $this->tenant();
        $user = $this->member($team, Role::Employee);

        $this->actingAs($user)
            ->get(route('profile.index'))
            ->assertOk()
            ->assertSee('Môj profil')
            ->assertSee($user->name)
            ->assertSee($user->lastname)
            ->assertSee($user->email)
            ->assertSee('Frekvencia zmien')
            ->assertSee('Absencie')
            ->assertSee('Zmena hesla');
    }

    public function test_user_can_filter_profile_by_date(): void
    {
        $team = $this->tenant();
        $user = $this->member($team, Role::Employee);

        $position = Position::factory()->create(['team_id' => $team->id]);
        Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'position_id' => $position->id,
            'date' => now()->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('profile.index', ['date' => 'month']))
            ->assertOk()
            ->assertSee('Tento mesiac')
            ->assertSee('Odpracované dni');
    }

    public function test_user_can_view_another_member_profile_in_same_team(): void
    {
        $team = $this->tenant();
        $viewer = $this->member($team, Role::Manager);
        $target = $this->member($team, Role::Employee);

        Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $target->id,
            'reason' => 'Dovolenka na horách',
            'date_from' => now()->addDays(2)->toDateString(),
            'date_to' => now()->addDays(5)->toDateString(),
            'status' => AbsenceStatus::Active,
        ]);

        $this->actingAs($viewer)
            ->get(route('profile.show', $target))
            ->assertOk()
            ->assertSee('Profil používateľa')
            ->assertSee($target->name)
            ->assertSee('Dovolenka na horách')
            ->assertSee('Aktívna');
    }

    public function test_back_button_preserves_origin_page_across_profile_actions(): void
    {
        $team = $this->tenant();
        $viewer = $this->member($team, Role::Manager);
        $target = $this->member($team, Role::Employee);

        $originUrl = route('admin.users.index');

        // 1. Enter profile from admin.users.index
        $response = $this->actingAs($viewer)
            ->from($originUrl)
            ->get(route('profile.show', $target));

        $response->assertOk()
            ->assertSee('href="'.$originUrl.'"', false);

        // 2. Subsequent request on the profile page itself (e.g. ?page=2 or ?date=month) does not overwrite originUrl
        $followUp = $this->actingAs($viewer)
            ->from(route('profile.show', $target))
            ->get(route('profile.show', ['user' => $target->id, 'page' => 2]));

        $followUp->assertOk()
            ->assertSee('href="'.$originUrl.'"', false);
    }
}
