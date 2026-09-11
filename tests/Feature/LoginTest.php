<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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

        $this->get(route('login'))->assertOk()->assertSee('čaká sa na schválenie vedúcim', false);
    }

    /**
     * The first of the two gates. An unverified account is not logged out - it is parked on the
     * notice page, which is where the "send it again" button lives.
     */
    public function test_an_unverified_member_is_sent_to_the_verification_notice(): void
    {
        $team = $this->tenant();
        $unverified = User::factory()->unverified()->memberOf($team)->create(['current_team_id' => $team->id]);

        $this->post(route('login.auth'), ['email' => $unverified->email, 'password' => 'password']);

        $this->get(route('calendar.index'))->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();
    }

    /**
     * An account hashed at the old cost factor still logs in, and comes out stored at the new one.
     * The upgrade must be invisible: same password, same session, stronger hash.
     */
    public function test_a_password_hashed_at_an_older_cost_is_upgraded_on_login(): void
    {
        $team = $this->tenant();

        // The suite itself runs at rounds 4 for speed, so both ends have to be set explicitly for
        // the assertion to mean anything: raise the configured cost first (and drop the already
        // resolved hasher, which captured the old one), then hash below it.
        config(['hashing.bcrypt.rounds' => 6]);
        Hash::forgetDrivers();

        $legacy = Hash::driver('bcrypt')->make('password', ['rounds' => 4]);
        $user = User::factory()->memberOf($team)->create([
            'current_team_id' => $team->id,
            'password' => $legacy,
        ]);

        $this->post(route('login.auth'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('calendar.index'));

        $stored = $user->fresh()->password;

        $this->assertNotSame($legacy, $stored, 'The weak hash must not survive a successful login.');
        $this->assertFalse(Hash::needsRehash($stored), 'It is stored at the configured cost now.');
        $this->assertTrue(Hash::check('password', $stored), 'And the password still works.');
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
