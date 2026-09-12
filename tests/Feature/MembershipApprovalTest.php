<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\UsersDataTable;
use App\Models\Team;
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
        $admin = $this->member($team, Role::HeadManager);
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
    /**
     * Denial must not leave the membership *approved*.
     *
     * `approved_at` used to be stamped here, exactly as accept() does, with only `is_active`
     * keeping the person out. Since `is_active` is editable from the user edit form, re-enabling
     * a denied account silently handed them a membership nobody ever approved.
     */
    public function test_denying_a_member_leaves_the_membership_unapproved(): void
    {
        $team = $this->tenant();
        $admin = $this->member($team, Role::HeadManager);
        $pending = User::factory()->pendingIn($team)->create();

        Livewire::actingAs($admin)
            ->test(UsersDataTable::class)
            ->call('deny', $pending->id);

        $this->assertFalse($pending->fresh()->is_active);
        $this->assertNull($team->users()->find($pending->id)->pivot->approved_at);
        $this->assertModelExists($pending);

        // The belt-and-braces check: even flipped back to active they are still only a candidate.
        $pending->update(['is_active' => true]);
        $pending->forgetApprovedTeamsCache();

        $this->assertFalse($pending->fresh()->isApprovedIn($team));
    }

    /**
     * Blocking the account is global, so refusing somebody here must not lock them out of a
     * cinema that did approve them - only the membership in *this* cinema is refused.
     */
    public function test_denying_a_member_of_two_cinemas_does_not_block_the_account(): void
    {
        $team = $this->tenant();
        $admin = $this->member($team, Role::HeadManager);

        $other = Team::factory()->create();
        $pending = User::factory()->pendingIn($team)->create();
        $pending->teams()->attach($other, ['approved_at' => now()]);

        Livewire::actingAs($admin)
            ->test(UsersDataTable::class)
            ->call('deny', $pending->id);

        $this->assertTrue($pending->fresh()->is_active, 'The other cinema still employs them.');
        $this->assertNull($team->users()->find($pending->id)->pivot->approved_at);
    }

    /**
     * Registration is two gates and only the second one is a decision anybody makes here, so
     * somebody who has not confirmed their address is not yet a candidate and stays out of the
     * queue. accept() refuses them anyway - this keeps them from sitting in the list as a row
     * nobody is allowed to act on.
     */
    public function test_the_user_list_only_shows_members_who_confirmed_their_e_mail(): void
    {
        $team = $this->tenant();
        $admin = $this->member($team, Role::HeadManager);

        $confirmed = User::factory()->pendingIn($team)->create(['lastname' => 'Overeny']);
        $unconfirmed = User::factory()->unverified()->pendingIn($team)->create(['lastname' => 'Neovereny']);

        Livewire::actingAs($admin)
            ->test(UsersDataTable::class)
            ->assertSee($confirmed->lastname)
            ->assertDontSee($unconfirmed->lastname);
    }

    public function test_pending_users_count_in_navbar_only_includes_email_verified_users(): void
    {
        $team = $this->tenant();
        $admin = $this->member($team, Role::HeadManager);

        // An unverified user does not increment the count
        User::factory()->unverified()->pendingIn($team)->create();
        $this->assertSame(0, $team->pendingUsers()->count());

        // Once email is verified, they are counted and badge shows up
        User::factory()->pendingIn($team)->create();
        $this->assertSame(1, $team->pendingUsers()->count());

        $this->actingAs($admin)
            ->get(route('admin.users.index'))
            ->assertSee('Používatelia')
            ->assertSee('1');
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
