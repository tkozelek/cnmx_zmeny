<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\DayCard;
use App\Livewire\ExtraNote;
use App\Models\Assignment;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The per-day Livewire component that replaced the `calendar.toggleUser` AJAX endpoint.
 */
class DayCardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Signing up picks no position - that is an admin's job later - so `position_id` is null.
     */
    public function test_signing_up_creates_an_assignment_for_that_day(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $date = CarbonImmutable::now()->addDays(3)->toDateString();

        Livewire::actingAs($user)
            ->test(DayCard::class, $this->props($date))
            ->call('signUp')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('assignments', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date' => $date,
            'position_id' => null,
            'created_by' => null,
        ]);
    }

    /**
     * The shared "Extra info" field lives in the session, so the card picks it up without it
     * being passed in - that is what lets one typed note apply to every day signed up.
     */
    public function test_signing_up_attaches_the_shared_extra_note(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $date = CarbonImmutable::now()->addDays(3)->toDateString();

        session([ExtraNote::SESSION_KEY => 'od 15:00']);

        Livewire::actingAs($user)
            ->test(DayCard::class, $this->props($date))
            ->call('signUp');

        $this->assertDatabaseHas('assignments', ['date' => $date, 'note' => 'od 15:00']);
    }

    /** One signup per person per day, and a double click must not blow up on the unique key. */
    public function test_signing_up_twice_is_idempotent(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $date = CarbonImmutable::now()->addDays(3)->toDateString();

        $card = Livewire::actingAs($user)->test(DayCard::class, $this->props($date));
        $card->call('signUp');
        $card->call('signUp');

        $this->assertDatabaseCount('assignments', 1);
    }

    /** Already signed up: the button becomes "Odpísať" and withdraw removes the row. */
    public function test_an_already_signed_up_day_offers_withdrawal(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $date = CarbonImmutable::now()->addDays(3)->toDateString();

        $assignment = Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'position_id' => null,
            'date' => $date,
        ]);

        Livewire::actingAs($user)
            ->test(DayCard::class, $this->props($date))
            ->assertSee('Odpísať')
            ->assertDontSee('Zapísať')
            ->call('withdraw');

        $this->assertModelMissing($assignment);
    }

    public function test_a_user_can_remove_their_own_assignment(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $date = CarbonImmutable::now()->addDays(3)->toDateString();

        $assignment = Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'position_id' => null,
            'date' => $date,
        ]);

        Livewire::actingAs($user)
            ->test(DayCard::class, $this->props($date))
            ->call('remove', $assignment->id)
            ->assertDispatched('toast');

        $this->assertModelMissing($assignment);
    }

    public function test_a_user_cannot_remove_someone_elses_assignment(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $colleague = $this->member($team);
        $date = CarbonImmutable::now()->addDays(3)->toDateString();

        $assignment = Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $colleague->id,
            'position_id' => null,
            'date' => $date,
        ]);

        Livewire::actingAs($user)
            ->test(DayCard::class, $this->props($date))
            ->call('remove', $assignment->id)
            ->assertForbidden();

        $this->assertModelExists($assignment);
    }

    /**
     * The card renders as locked, but the action is guarded too - a locked week must not be
     * writable by anyone replaying the Livewire request directly.
     */
    public function test_signing_up_in_a_locked_week_is_refused(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $date = CarbonImmutable::now()->addDays(3);

        $team->weekLocks()->create([
            'week_start' => app(WeekService::class)->start($team, $date)->toDateString(),
        ]);

        Livewire::actingAs($user)
            ->test(DayCard::class, $this->props($date->toDateString(), locked: true))
            ->call('signUp')
            ->assertForbidden();

        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_an_admin_can_remove_anyones_assignment(): void
    {
        $team = $this->tenant();
        $admin = $this->member($team, Role::Admin);
        $employee = $this->member($team);
        $date = CarbonImmutable::now()->addDays(3)->toDateString();

        $assignment = Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $employee->id,
            'position_id' => null,
            'date' => $date,
        ]);

        Livewire::actingAs($admin)
            ->test(DayCard::class, $this->props($date))
            ->call('remove', $assignment->id);

        $this->assertModelMissing($assignment);
    }

    /**
     * @return array<string, mixed>
     */
    private function props(string $date, bool $locked = false): array
    {
        return ['date' => $date, 'locked' => $locked];
    }
}
