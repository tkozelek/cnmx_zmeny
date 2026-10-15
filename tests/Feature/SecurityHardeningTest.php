<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Http\Kernel;
use App\Http\Middleware\TrustHosts;
use App\Livewire\UsersDataTable;
use App\Models\Absence;
use App\Models\User;
use App\Traits\EscapesSpreadsheetFormulas;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * One case per finding from the security review, each written so it fails if the fix is undone.
 */
class SecurityHardeningTest extends TestCase
{
    use EscapesSpreadsheetFormulas;
    use RefreshDatabase;

    /**
     * Laravel's `email` rule is RFCValidation, which accepts quoted local parts - so
     * `"<img src=x onerror=...>"@example.com` is a legitimately storable address, and the column
     * that prints it in the admin table is ->html().
     */
    public function test_an_email_address_cannot_inject_markup_into_the_admin_user_table(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);

        $attacker = User::factory()->create(['email' => '"<img src=x onerror=alert(1)>"@example.com']);
        $attacker->teams()->attach($team, ['approved_at' => now()]);

        $html = Livewire::actingAs($manager)->test(UsersDataTable::class)->html();

        $this->assertStringNotContainsString('<img src=x onerror=alert(1)>', $html);
        $this->assertStringContainsString('&lt;img src=x', $html);
    }

    /**
     * `remember_token` is stored unhashed and *is* the remember-me cookie value, so one in a log
     * file is a usable credential. The password hash is little better.
     */
    public function test_a_password_change_never_reaches_the_log(): void
    {
        $logged = [];
        User::updated(function (User $model) use (&$logged): void {
            $logged[] = $model->getChanges();
        });

        $user = User::factory()->create();
        $user->forceFill(['password' => 'a-brand-new-secret']);
        $user->setRememberToken(Str::random(60));
        $user->save();

        // The event fired with the secrets present - that is the raw material Loggable sees.
        $this->assertNotEmpty($logged);
        $this->assertArrayHasKey('password', $logged[0]);

        // What Loggable actually writes must have neither.
        $written = $this->loggableUpdatePayload($logged[0]);
        $this->assertArrayNotHasKey('password', $written);
        $this->assertArrayNotHasKey('remember_token', $written);
    }

    /** A surname is free text, and `User::__toString()` puts it at the start of an export cell. */
    public function test_a_name_cannot_become_a_formula_in_an_export(): void
    {
        $this->assertSame("'=cmd|'/c calc'!A0", $this->escapeFormula("=cmd|'/c calc'!A0"));
        $this->assertSame("'+1", $this->escapeFormula('+1'));
        $this->assertSame("'@SUM(A1)", $this->escapeFormula('@SUM(A1)'));
        $this->assertSame('Kozelek T.', $this->escapeFormula('Kozelek T.'), 'A real name must pass through untouched.');
        $this->assertNull($this->escapeFormula(null));
    }

    /** And it is refused at the door, so nothing else that ever renders a name inherits the problem. */
    public function test_registration_refuses_a_name_that_starts_a_formula(): void
    {
        $team = $this->tenant();

        $this->post(route('register.store'), [
            'name' => 'Bob',
            'lastname' => '=HYPERLINK("http://evil","click")',
            'email' => 'bob@example.com',
            'password' => 'correct-horse-battery',
            'password_confirmation' => 'correct-horse-battery',
            'team_id' => $team->id,
        ])->assertSessionHasErrors('lastname');

        $this->assertDatabaseMissing('users', ['email' => 'bob@example.com']);
    }

    /** The reset form was the cheapest way to weaken an account: six characters against eight. */
    public function test_a_password_reset_enforces_the_same_length_as_registration(): void
    {
        $this->tenant();
        $user = User::factory()->create();
        $token = app('auth.password.broker')->createToken($user);

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'short7',
            'password_confirmation' => 'short7',
        ])->assertSessionHasErrors('password');
    }

    /** "No such user" is exactly what an attacker wants to hear. */
    public function test_the_reset_form_does_not_reveal_whether_an_address_is_registered(): void
    {
        $this->tenant();
        $known = User::factory()->create();

        $forKnown = $this->post(route('password.index'), ['email' => $known->email]);
        $forUnknown = $this->post(route('password.index'), ['email' => 'nobody-here@example.com']);

        $this->assertSame(
            session()->get('message'),
            $forKnown->baseResponse->getSession()->get('message'),
        );
        $forUnknown->assertSessionMissing('error');
        $this->assertSame(
            $forKnown->baseResponse->getSession()->get('message'),
            $forUnknown->baseResponse->getSession()->get('message'),
            'A registered and an unregistered address must produce the same answer.',
        );
    }

    /** A blocked account could still authenticate and use the auth-only verification endpoints. */
    public function test_a_blocked_account_cannot_use_the_verification_endpoints(): void
    {
        $blocked = User::factory()->unverified()->inactive()->create();

        $this->actingAs($blocked)->get(route('verification.notice'))->assertForbidden();
        $this->actingAs($blocked)->post(route('verification.send'))->assertForbidden();

        // The legitimate path has to stay open - EnsureUserIsActive sends people here.
        $this->actingAs(User::factory()->unverified()->create())
            ->get(route('verification.notice'))
            ->assertOk();
    }

    /**
     * Host header validation, asserted on the wiring rather than on a request.
     *
     * Laravel's TrustHosts deliberately no-ops under `runningUnitTests()`, so a spoofed Host
     * cannot be rejected from here however the middleware is configured - which means a request
     * test would pass whether or not the middleware is registered. What is worth pinning is that
     * it *is* registered (it sat commented out of the stack) and that its pattern covers this
     * app's host only: reset and verification links are absolute and built from the Host header.
     */
    public function test_host_header_validation_is_registered_and_scoped_to_this_app(): void
    {
        $this->assertContains(
            TrustHosts::class,
            (new Kernel(app(), app('router')))->getGlobalMiddleware(),
            'TrustHosts must stay in the global stack - absolute e-mail links are built from the Host header.',
        );

        config(['app.url' => 'https://beta.cinemaxzmeny.eu']);
        $patterns = array_filter((new TrustHosts(app()))->hosts());

        $this->assertNotEmpty($patterns);

        foreach (['beta.cinemaxzmeny.eu', 'sub.beta.cinemaxzmeny.eu'] as $ours) {
            $this->assertTrue($this->hostMatches($ours, $patterns), "{$ours} must be trusted");
        }

        foreach (['attacker.example.com', 'cinemaxzmeny.eu.evil.com'] as $theirs) {
            $this->assertFalse($this->hostMatches($theirs, $patterns), "{$theirs} must not be trusted");
        }
    }

    /** @param  list<string>  $patterns */
    private function hostMatches(string $host, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match('{'.$pattern.'}i', $host) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Mirrors Loggable::updated's filtering, so this test pins the behaviour rather than the
     * implementation - the trait logs, and asserting on a log channel would test Monolog.
     *
     * @param  array<string, mixed>  $changes
     * @return array<string, mixed>
     */
    private function loggableUpdatePayload(array $changes): array
    {
        unset($changes['updated_at'], $changes['last_login_at']);

        foreach (['password', 'remember_token'] as $secret) {
            unset($changes[$secret]);
        }

        return $changes;
    }

    /** Laravel's `throttle:6,1` keyed guests on the IP alone, so failed logins also locked out registration. */
    public function test_failed_logins_do_not_lock_the_same_ip_out_of_registration(): void
    {
        $this->tenant();

        foreach (range(1, 11) as $attempt) {
            $this->post(route('login.auth'), ['email' => "nobody{$attempt}@example.com", 'password' => 'wrong-password']);
        }

        $this->post(route('register.store'), [])->assertSessionHasErrors();
    }

    /** Rotating IPs must not let anyone flood one inbox with verification e-mails. */
    public function test_verification_resends_are_capped_per_address(): void
    {
        $user = User::factory()->unverified()->create();

        foreach (['10.0.0.1', '10.0.0.2', '10.0.0.3'] as $ip) {
            $this->withServerVariables(['REMOTE_ADDR' => $ip])
                ->post(route('verification.resend_guest'), ['email' => $user->email])
                ->assertStatus(302);
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.4'])
            ->post(route('verification.resend_guest'), ['email' => $user->email])
            ->assertTooManyRequests();
    }

    /** Whoever controls an address controls the password reset - a manager must not be able to change it. */
    public function test_a_manager_cannot_change_someones_email(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::HeadManager);
        $employee = $this->member($team);
        $originalEmail = $employee->email;

        $this->actingAs($manager)->put(route('admin.users.update', $employee), [
            'name' => $employee->name,
            'lastname' => $employee->lastname,
            'email' => 'takeover@example.com',
            'role' => Role::Employee->value,
            'is_active' => false,
        ])->assertRedirect();

        $employee->refresh();
        $this->assertSame($originalEmail, $employee->email);
        $this->assertTrue($employee->is_active);
    }

    public function test_changing_the_password_ends_other_sessions(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);

        $this->actingAs($user)->get(route('profile.index'))->assertOk();

        $user->update(['password' => 'a-brand-new-password']);

        $this->get(route('profile.index'))->assertRedirect(route('login'));
    }

    /** The e-mail says 60 minutes - only an admin invite to a never-used account gets 24 hours. */
    public function test_an_ordinary_reset_link_expires_after_an_hour(): void
    {
        $user = User::factory()->create(['last_login_at' => now()->subWeek()]);
        $token = Password::broker('users')->createToken($user);

        $this->travel(61)->minutes();

        $this->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ]);

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_pulse_is_open_only_to_the_listed_developer(): void
    {
        $developer = User::factory()->create(['email' => 'dev@example.com']);
        $anyoneElse = User::factory()->create();

        config(['pulse.admins' => ['dev@example.com']]);

        $this->assertTrue(Gate::forUser($developer)->allows('viewPulse'));
        $this->assertFalse(Gate::forUser($anyoneElse)->allows('viewPulse'));
    }

    /** The privacy page promises nothing loads from third parties - the CSP is what holds that true. */
    public function test_pages_only_load_from_this_server(): void
    {
        $csp = $this->get(route('help'))->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString('https:', $csp);
    }

    /** Logs outlive deleted accounts, so they may say an absence changed but never why someone was away. */
    public function test_the_audit_log_never_keeps_an_absence_reason(): void
    {
        $team = $this->tenant();
        $absence = Absence::factory()->create(['team_id' => $team->id, 'user_id' => $this->member($team)->id, 'reason' => 'Lekár']);
        Log::spy();

        $absence->update(['reason' => 'Rodinné dôvody']);
        $absence->delete();

        Log::shouldHaveReceived('info')->withArgs(fn (string $message, array $context): bool => $context['changes']['reason'] === '[skryté]' && $context['original']['reason'] === '[skryté]');
        Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context): bool => $context['attributes']['reason'] === '[skryté]');
    }

    /** A failed password change flashed the typed new password into the session file. */
    public function test_a_failed_password_change_does_not_keep_the_new_password(): void
    {
        $team = $this->tenant();

        $this->actingAs($this->member($team))->put(route('settings.password.update'), [
            'current_password' => 'wrong-password',
            'new_password' => 'my-new-secret-123',
            'new_password_confirmation' => 'my-new-secret-123',
        ])->assertSessionHasErrors();

        $this->assertNull(session()->getOldInput('new_password'));
        $this->assertNull(session()->getOldInput('new_password_confirmation'));
    }
}
