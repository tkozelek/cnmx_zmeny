<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\UsersDataTable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Approving a new member, and the gate that keeps them out until then.
 */
class MembershipApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_approve_a_pending_member(): void
    {
        $team = $this->tenant();
        $admin = $this->member($team, Role::Admin);
        $pending = User::factory()->pendingIn($team)->create();

        Livewire::actingAs($admin)
            ->test(UsersDataTable::class)
            ->call('accept', $pending->id)
            ->assertDispatched('toast');

        $this->assertNotNull($team->users()->find($pending->id)->pivot->approved_at);
    }

    /**
     * Denying deactivates rather than deletes - payroll rows are RESTRICT - and clears the
     * pending state, otherwise the row keeps showing accept/deny instead of "Zablokovaný".
     */
    public function test_denying_a_member_deactivates_the_account(): void
    {
        $team = $this->tenant();
        $admin = $this->member($team, Role::Admin);
        $pending = User::factory()->pendingIn($team)->create();

        Livewire::actingAs($admin)
            ->test(UsersDataTable::class)
            ->call('deny', $pending->id);

        $this->assertFalse($pending->fresh()->is_active);
        $this->assertNotNull($team->users()->find($pending->id)->pivot->approved_at);
        $this->assertModelExists($pending);
    }

    /**
     * No approved membership means no access - the state that used to be the "neovereny"
     * role. The user is logged back out, not merely redirected.
     */
    public function test_a_pending_member_cannot_reach_the_calendar(): void
    {
        $team = $this->tenant();
        $pending = User::factory()->pendingIn($team)->create(['current_team_id' => $team->id]);

        $this->actingAs($pending)
            ->get(route('calendar.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    /** Blocked is `is_active = false` - the state that used to be the "zablokovany" role. */
    public function test_a_blocked_member_cannot_reach_the_calendar(): void
    {
        $team = $this->tenant();
        $blocked = User::factory()->memberOf($team, Role::Employee->value)->inactive()->create();

        $this->actingAs($blocked)
            ->get(route('calendar.index'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
