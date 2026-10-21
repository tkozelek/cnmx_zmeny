<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ConfirmEmailChange;
use App\Notifications\VerifyNewEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    /** The phone layout choice is stored on the account and reaches the week page. */
    public function test_choosing_the_compact_week_switches_the_calendar_layout(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);

        $this->actingAs($user)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertDontSee('week-compact');

        $this->actingAs($user)
            ->put(route('settings.update'), ['compact_week' => '1'])
            ->assertRedirect(route('settings.index').'#zobrazenie');

        $this->assertTrue($user->fresh()->compact_week);

        $this->actingAs($user)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('week-compact');
    }

    /**
     * The address only moves once the old inbox approved and the new one answered - and opening
     * the old inbox's link (as a mail scanner would) approves nothing by itself.
     */
    public function test_an_email_change_needs_the_old_inbox_and_then_the_new_one(): void
    {
        Notification::fake();
        $user = $this->member($this->tenant());
        $oldEmail = $user->email;

        $this->actingAs($user)
            ->put(route('settings.email.update'), ['email' => 'nova@kino.test', 'password' => 'password'])
            ->assertRedirect(route('settings.index').'#email');

        $this->assertSame($oldEmail, $user->fresh()->email);
        $approveUrl = $this->sentConfirmationUrl($user);

        auth()->logout();
        $this->get($approveUrl)->assertOk()->assertSee('nova@kino.test');
        $this->assertNull($user->fresh()->pending_email_approved_at);
        Notification::assertSentOnDemandTimes(VerifyNewEmail::class, 0);

        $this->post($approveUrl)->assertOk()->assertSee('Zmena potvrdená');
        $this->assertSame($oldEmail, $user->fresh()->email);

        $this->get($this->sentVerificationUrl())->assertOk()->assertSee('E-mail zmenený');

        $user->refresh();
        $this->assertSame('nova@kino.test', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertNotNull($user->email_verified_at);
    }

    /** A link from an earlier request dies when a new one replaces it - even for the same address - and the new inbox can never skip the old one. */
    public function test_stale_or_skipped_email_change_links_are_refused(): void
    {
        Notification::fake();
        $user = $this->member($this->tenant());

        $this->actingAs($user)->put(route('settings.email.update'), ['email' => 'nova@kino.test', 'password' => 'password']);
        $staleUrl = $this->sentConfirmationUrl($user);

        $this->actingAs($user)->put(route('settings.email.update'), ['email' => 'nova@kino.test', 'password' => 'password']);

        $this->post($staleUrl)->assertForbidden();

        // Even holding the current token, the new inbox's link is refused until the old inbox approved.
        $this->get(URL::temporarySignedRoute('email-change.verify', now()->addHour(), [
            'id' => $user->id,
            'token' => $user->fresh()->pending_email_token,
        ]))->assertForbidden();

        $this->assertNotSame('nova@kino.test', $user->fresh()->email);
    }

    /** Someone registered the address while the change waited - the change is dropped, not a 500. */
    public function test_an_email_change_to_an_address_taken_meanwhile_is_dropped(): void
    {
        Notification::fake();
        $user = $this->member($this->tenant());
        $oldEmail = $user->email;

        $this->actingAs($user)->put(route('settings.email.update'), ['email' => 'nova@kino.test', 'password' => 'password']);
        $this->post($this->sentConfirmationUrl($user));

        User::factory()->create(['email' => 'nova@kino.test']);

        $this->get($this->sentVerificationUrl())->assertOk()->assertSee('Adresa je už obsadená');

        $this->assertSame($oldEmail, $user->fresh()->email);
        $this->assertNull($user->fresh()->pending_email);
    }

    /** The owner of the old inbox can stop a change they never asked for. */
    public function test_the_old_inbox_can_decline_an_email_change(): void
    {
        Notification::fake();
        $user = $this->member($this->tenant());

        $this->actingAs($user)->put(route('settings.email.update'), ['email' => 'utocnik@kino.test', 'password' => 'password']);

        $this->delete($this->sentConfirmationUrl($user))->assertOk()->assertSee('Zmena zamietnutá');

        $this->assertNull($user->fresh()->pending_email);
        Notification::assertSentOnDemandTimes(VerifyNewEmail::class, 0);
    }

    /** The approve link from the last mail the old inbox got. */
    private function sentConfirmationUrl(User $user): string
    {
        $url = null;

        Notification::assertSentTo($user, ConfirmEmailChange::class, function (ConfirmEmailChange $notification) use ($user, &$url): bool {
            $url = $notification->toMail($user)->actionUrl;

            return true;
        });

        return $url;
    }

    /** The verify link from the mail the new inbox got - asserting it went to the new address. */
    private function sentVerificationUrl(): string
    {
        $url = null;

        Notification::assertSentOnDemand(VerifyNewEmail::class, function (VerifyNewEmail $notification, array $channels, AnonymousNotifiable $notifiable) use (&$url): bool {
            $url = $notification->toMail($notifiable)->actionUrl;

            return $notifiable->routes['mail'] === 'nova@kino.test';
        });

        return $url;
    }
}
