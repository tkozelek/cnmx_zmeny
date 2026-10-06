<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\DayCard;
use App\Livewire\ExtraNote;
use App\Models\Assignment;
use App\Policies\AssignmentPolicy;
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
            'created_by' => null,
        ]);
    }

    /**
     * The shared "Poznámka" field lives in the session, so the card picks it up without it
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
            'date' => $date,
        ]);

        Livewire::actingAs($user)
            ->test(DayCard::class, $this->props($date))
            ->call('remove', $assignment->id)
            ->assertForbidden();

        $this->assertModelExists($assignment);
    }

    /**
     * A page opened before the manager locked the week still shows "Zapísať sa" - the click is
     * refused with the reason as a toast (not a 403 page) and the card switches to locked.
     */
    public function test_signing_up_in_a_locked_week_is_refused_with_a_reason(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $date = CarbonImmutable::now()->addDays(3);

        $team->weekLocks()->create([
            'week_start' => app(WeekService::class)->start($team, $date)->toDateString(),
        ]);

        Livewire::actingAs($user)
            ->test(DayCard::class, $this->props($date->toDateString()))
            ->call('signUp')
            ->assertDispatched('toast', type: 'error', message: AssignmentPolicy::LOCKED_MESSAGE)
            ->assertSet('locked', true);

        $this->assertDatabaseCount('assignments', 0);
    }

    public function test_an_admin_can_remove_anyones_assignment(): void
    {
        $team = $this->tenant();
        $admin = $this->member($team, Role::HeadManager);
        $employee = $this->member($team);
        $date = CarbonImmutable::now()->addDays(3)->toDateString();

        $assignment = Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $employee->id,
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
    public function test_signed_up_people_drop_out_of_the_draw_pool(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $signedUp = $this->member($team);
        $free = $this->member($team);
        $date = CarbonImmutable::now()->addDays(3)->toDateString();

        Assignment::factory()->create(['team_id' => $team->id, 'user_id' => $signedUp->id, 'date' => $date]);

        $card = Livewire::actingAs($manager)->test(DayCard::class, [
            ...$this->props($date),
            'available' => [$signedUp->id => (string) $signedUp, $free->id => (string) $free],
        ]);

        $this->assertSame([$free->id => (string) $free], $card->instance()->drawable);
        $card->assertSee('K dispozícii na losovanie (1)');
    }

    private function props(string $date, bool $locked = false): array
    {
        return ['date' => $date, 'locked' => $locked];
    }
}
