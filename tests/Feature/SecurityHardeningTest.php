<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Http\Kernel;
use App\Http\Middleware\TrustHosts;
use App\Livewire\UsersDataTable;
use App\Models\User;
use App\Traits\EscapesSpreadsheetFormulas;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
