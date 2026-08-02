<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Logging in, and - mostly - the messages shown when it does not work. Every rejection has to
 * say something accurate and say it on the login page.
 */
class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_approved_member_can_log_in(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);

        $this->post(route('login.auth'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('calendar.index'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_password_is_reported_on_the_email_field(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);

        $this->from(route('login'))
            ->post(route('login.auth'), ['email' => $user->email, 'password' => 'wrong-password'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Nesprávny email alebo heslo.']);

        $this->assertGuest();
    }

    public function test_an_unknown_email_is_reported_the_same_way(): void
    {
        $this->tenant();

        $this->from(route('login'))
            ->post(route('login.auth'), ['email' => 'nikto@kino.test', 'password' => 'whatever'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_the_error_is_rendered_on_the_login_page(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);

        $this->from(route('login'))
            ->post(route('login.auth'), ['email' => $user->email, 'password' => 'wrong-password']);

        // Not merely flashed - actually visible when the page comes back.
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Nesprávny email alebo heslo.');
    }

    /**
     * A blocked account must be told it is blocked, not that its password is wrong. This is why
     * `is_active` is deliberately not folded into the credentials check.
     */
    public function test_a_blocked_account_is_told_it_is_blocked(): void
    {
        $team = $this->tenant();
        $blocked = User::factory()->memberOf($team, 'employee')->inactive()->create();

        $this->post(route('login.auth'), ['email' => $blocked->email, 'password' => 'password'])
            ->assertRedirect(route('calendar.index'));

        // EnsureUserIsActive catches it on the next request and ejects them with the reason.
        $this->get(route('calendar.index'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->get(route('login'))->assertOk()->assertSee('Účet je zablokovaný.');
    }

    public function test_a_pending_member_is_told_to_wait_for_approval(): void
    {
        $team = $this->tenant();
        $pending = User::factory()->pendingIn($team)->create(['current_team_id' => $team->id]);

        $this->post(route('login.auth'), ['email' => $pending->email, 'password' => 'password']);

        $this->get(route('calendar.index'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->get(route('login'))->assertOk()->assertSee('Ešte si nebol/a overený', false);
    }

    public function test_a_missing_email_is_a_validation_error(): void
    {
        $this->tenant();

        $this->from(route('login'))
            ->post(route('login.auth'), ['password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
