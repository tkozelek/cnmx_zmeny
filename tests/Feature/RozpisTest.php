<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\RozpisDay;
use App\Models\Assignment;
use App\Models\Position;
use App\Models\PositionSlot;
use App\Models\Team;
use App\Models\User;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The manager's step, gated the opposite way to the calendar: the builder opens only once the
 * week is locked. That inversion is the part worth pinning down.
 */
class RozpisTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_manager_places_a_signed_up_employee_on_a_position(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $employee = $this->member($team);
        $date = $this->workday($team);

        $this->lockWeekOf($team, $date);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create(['start_time' => '16:30:00']);

        $assignment = $this->signUp($team, $employee, $date);

        Livewire::actingAs($manager)
            ->test(RozpisDay::class, ['date' => $date])
            ->call('place', $assignment->id, $slot->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('assignments', [
            'id' => $assignment->id,
            'position_id' => $position->id,
            'position_slot_id' => $slot->id,
            'start_time' => '16:30:00',
        ]);
    }

    /** Locking is the trigger: before it, the week still belongs to the employees. */
    public function test_placing_on_an_unlocked_week_is_refused(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $employee = $this->member($team);
        $date = $this->workday($team);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create();

        $assignment = $this->signUp($team, $employee, $date);

        Livewire::actingAs($manager)
            ->test(RozpisDay::class, ['date' => $date])
            ->call('place', $assignment->id, $slot->id)
            ->assertForbidden();

        $this->assertDatabaseHas('assignments', ['id' => $assignment->id, 'position_id' => null]);
    }

    public function test_an_employee_cannot_place_anyone(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);
        $date = $this->workday($team);

        $this->lockWeekOf($team, $date);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create();

        $assignment = $this->signUp($team, $employee, $date);

        Livewire::actingAs($employee)
            ->test(RozpisDay::class, ['date' => $date])
            ->call('place', $assignment->id, $slot->id)
            ->assertForbidden();

        $this->assertDatabaseHas('assignments', ['id' => $assignment->id, 'position_id' => null]);
    }

    /**
     * A slot holds one person: dropping someone onto an occupied slot replaces the occupant
     * rather than leaving two people on one position.
     */
    public function test_placing_onto_an_occupied_slot_returns_the_previous_occupant_to_the_pool(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $first = $this->member($team);
        $second = $this->member($team);
        $date = $this->workday($team);

        $this->lockWeekOf($team, $date);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create();

        $placed = $this->signUp($team, $first, $date, $slot);
        $waiting = $this->signUp($team, $second, $date);

        Livewire::actingAs($manager)
            ->test(RozpisDay::class, ['date' => $date])
            ->call('place', $waiting->id, $slot->id);

        $this->assertDatabaseHas('assignments', ['id' => $waiting->id, 'position_slot_id' => $slot->id]);
        $this->assertDatabaseHas('assignments', ['id' => $placed->id, 'position_id' => null, 'position_slot_id' => null]);
    }

    /**
     * The point of slots: a busy Friday needs three people on the bufet, which is three slots of
     * one position — not three positions called "Bufet", "Bufet 2", "Bufet 3".
     */
    public function test_one_position_can_be_offered_several_times_in_a_day(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $date = $this->workday($team);

        $this->lockWeekOf($team, $date);

        // No code, so label() falls back to the name and the assertion below reads plainly.
        $bufet = Position::factory()->create(['team_id' => $team->id, 'name' => 'Bufet', 'code' => null]);

        $component = Livewire::actingAs($manager)->test(RozpisDay::class, ['date' => $date]);

        foreach (range(1, 3) as $ignored) {
            $component->set('newPositionId', $bufet->id)->call('addSlot');
        }

        $this->assertSame(3, PositionSlot::where('date', $date)->count());

        // Numbered for display only once there is more than one of them.
        $this->assertSame(['Bufet 1', 'Bufet 2', 'Bufet 3'], array_values($component->get('slotLabels')));

        // And each row holds its own person.
        $slots = PositionSlot::where('date', $date)->orderBy('id')->get();
        $first = $this->signUp($team, $this->member($team), $date);
        $second = $this->signUp($team, $this->member($team), $date);

        $component->call('place', $first->id, $slots[0]->id)
            ->call('place', $second->id, $slots[1]->id);

        $this->assertDatabaseHas('assignments', ['id' => $first->id, 'position_slot_id' => $slots[0]->id]);
        $this->assertDatabaseHas('assignments', ['id' => $second->id, 'position_slot_id' => $slots[1]->id]);
    }

    /**
     * A row's time is editable after the fact, and whoever is standing in it moves with it —
     * they took those times from the slot in the first place.
     */
    public function test_editing_a_slot_time_moves_its_occupant_too(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $date = $this->workday($team);

        $this->lockWeekOf($team, $date);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create(['start_time' => '16:00:00']);
        $placed = $this->signUp($team, $this->member($team), $date, $slot);

        Livewire::actingAs($manager)
            ->test(RozpisDay::class, ['date' => $date])
            ->call('updateSlotTime', $slot->id, '17:30')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('position_slots', ['id' => $slot->id, 'start_time' => '17:30:00']);
        $this->assertDatabaseHas('assignments', ['id' => $placed->id, 'start_time' => '17:30:00']);
    }

    /** Clearing the time is a legitimate edit, not a malformed one. */
    public function test_a_slot_time_can_be_cleared(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $date = $this->workday($team);

        $this->lockWeekOf($team, $date);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create(['start_time' => '16:00:00']);

        Livewire::actingAs($manager)
            ->test(RozpisDay::class, ['date' => $date])
            ->call('updateSlotTime', $slot->id, null);

        $this->assertDatabaseHas('position_slots', ['id' => $slot->id, 'start_time' => null]);
    }

    public function test_an_employee_cannot_change_a_slot_time(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);
        $date = $this->workday($team);

        $this->lockWeekOf($team, $date);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create(['start_time' => '16:00:00']);

        Livewire::actingAs($employee)
            ->test(RozpisDay::class, ['date' => $date])
            ->call('updateSlotTime', $slot->id, '20:00')
            ->assertForbidden();

        $this->assertDatabaseHas('position_slots', ['id' => $slot->id, 'start_time' => '16:00:00']);
    }

    /** Copying one row to chosen days — "I built Thursday's bufet, put it on Friday too". */
    public function test_a_single_slot_copies_onto_the_days_the_manager_picks(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $weekStart = $this->weekStart($team);

        $source = $weekStart->toDateString();
        $friday = $weekStart->addDay()->toDateString();
        $saturday = $weekStart->addDays(2)->toDateString();
        $untouched = $weekStart->addDays(3)->toDateString();

        $this->lockWeekOf($team, $source);

        $bufet = Position::factory()->create(['team_id' => $team->id, 'name' => 'Bufet']);
        $slot = PositionSlot::factory()->forPosition($bufet)->on($source)->create(['start_time' => '16:00:00']);

        Livewire::actingAs($manager)
            ->test(RozpisDay::class, ['date' => $source])
            ->call('copySlot', $slot->id, [$friday, $saturday])
            ->assertDispatched('toast');

        foreach ([$friday, $saturday] as $copiedTo) {
            $this->assertDatabaseHas('position_slots', [
                'date' => $copiedTo,
                'position_id' => $bufet->id,
                'start_time' => '16:00:00',
            ]);
        }

        $this->assertDatabaseMissing('position_slots', ['date' => $untouched]);
    }

    /** A hand-crafted request cannot push a slot into a day the UI never offered. */
    public function test_copying_a_slot_to_a_day_outside_the_week_is_ignored(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $date = $this->workday($team);

        $this->lockWeekOf($team, $date);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $slot = PositionSlot::factory()->forPosition($position)->on($date)->create();

        $farAway = CarbonImmutable::parse($date)->addMonth()->toDateString();

        Livewire::actingAs($manager)
            ->test(RozpisDay::class, ['date' => $date])
            ->call('copySlot', $slot->id, [$farAway]);

        $this->assertDatabaseMissing('position_slots', ['date' => $farAway]);
    }

    /** Copy is additive — it must never clobber a day the manager already built. */
    public function test_copying_a_layout_leaves_existing_slots_alone(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $weekStart = $this->weekStart($team);
        $source = $weekStart->toDateString();
        $target = $weekStart->addDay()->toDateString();

        $this->lockWeekOf($team, $source);

        $bufet = Position::factory()->create(['team_id' => $team->id, 'name' => 'Bufet']);
        $kasa = Position::factory()->create(['team_id' => $team->id, 'name' => 'Pokladňa']);

        PositionSlot::factory()->forPosition($bufet)->on($source)->create(['start_time' => '16:00:00']);
        PositionSlot::factory()->forPosition($kasa)->on($source)->create(['start_time' => '17:00:00']);

        // The target already offers Bufet, at a time the manager chose.
        PositionSlot::factory()->forPosition($bufet)->on($target)->create(['start_time' => '10:00:00']);

        $this->actingAs($manager)
            ->post(route('rozpis.copy', ['date' => $target]), ['source_date' => $source])
            ->assertRedirect(route('rozpis.show', ['date' => $weekStart->toDateString()]));

        // Pokladňa copied over; the pre-existing Bufet slot kept its own start time.
        $this->assertDatabaseHas('position_slots', ['date' => $target, 'position_id' => $kasa->id, 'start_time' => '17:00:00']);
        $this->assertDatabaseHas('position_slots', ['date' => $target, 'position_id' => $bufet->id, 'start_time' => '10:00:00']);
        $this->assertSame(2, PositionSlot::where('date', $target)->count());
    }

    public function test_the_builder_redirects_to_the_calendar_until_the_week_is_locked(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $weekStart = $this->weekStart($team);

        $this->actingAs($manager)
            ->get(route('rozpis.show', ['date' => $weekStart->toDateString()]))
            ->assertRedirect(route('calendar.show', ['date' => $weekStart->toDateString()]))
            ->assertSessionHas('message', 'Najprv zamknite týždeň.');
    }

    /** Renders the page and all seven day components — a Blade typo would otherwise only show in the browser. */
    public function test_the_builder_renders_the_locked_week(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $employee = $this->member($team);
        $weekStart = $this->weekStart($team);
        $date = $weekStart->addDay()->toDateString();

        $this->lockWeekOf($team, $date);

        $position = Position::factory()->create(['team_id' => $team->id, 'name' => 'Bufet', 'code' => 'BUF']);
        PositionSlot::factory()->forPosition($position)->on($date)->create(['start_time' => '16:00:00']);
        $this->signUp($team, $employee, $date);

        $this->actingAs($manager)
            ->get(route('rozpis.show', ['date' => $weekStart->toDateString()]))
            ->assertOk()
            ->assertSee('Rozpis zmien')
            ->assertSee('BUF')
            ->assertSee('Nezaradení')
            ->assertSee((string) $employee);
    }

    public function test_adding_a_slot_requires_the_week_to_be_locked(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $date = $this->workday($team);
        $position = Position::factory()->create(['team_id' => $team->id]);

        Livewire::actingAs($manager)
            ->test(RozpisDay::class, ['date' => $date])
            ->set('newPositionId', $position->id)
            ->call('addSlot')
            ->assertForbidden();

        $this->assertDatabaseCount('position_slots', 0);
    }

    public function test_adding_a_slot_on_a_locked_week_offers_the_position_for_that_day(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $date = $this->workday($team);
        $position = Position::factory()->create(['team_id' => $team->id]);

        $this->lockWeekOf($team, $date);

        Livewire::actingAs($manager)
            ->test(RozpisDay::class, ['date' => $date])
            ->set('newPositionId', $position->id)
            ->set('newStartTime', '18:00')
            ->call('addSlot')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('position_slots', [
            'team_id' => $team->id,
            'date' => $date,
            'position_id' => $position->id,
            // Stored as a full TIME: the picker sends H:i, PositionSlot normalises it.
            'start_time' => '18:00:00',
        ]);
    }

    /**
     * Row order is per day: dragging Thursday's rows must not touch the cinema-wide
     * `positions.sort_order`, or every other day would silently reorder too.
     */
    public function test_dragging_rows_reorders_only_that_day(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $date = $this->workday($team);
        $otherDay = CarbonImmutable::parse($date)->addDay()->toDateString();

        $this->lockWeekOf($team, $date);

        $first = Position::factory()->create(['team_id' => $team->id, 'name' => 'A', 'sort_order' => 10]);
        $second = Position::factory()->create(['team_id' => $team->id, 'name' => 'B', 'sort_order' => 20]);

        $slotA = PositionSlot::factory()->forPosition($first)->on($date)->create();
        $slotB = PositionSlot::factory()->forPosition($second)->on($date)->create();
        PositionSlot::factory()->forPosition($first)->on($otherDay)->create();

        Livewire::actingAs($manager)
            ->test(RozpisDay::class, ['date' => $date])
            ->call('reorderSlots', [$slotB->id, $slotA->id]);

        $this->assertTrue(
            $slotB->fresh()->sort_order < $slotA->fresh()->sort_order,
            'B was dragged above A on this day.'
        );

        $this->assertSame(10, $first->fresh()->sort_order, 'The cinema-wide position order is untouched.');
        $this->assertSame(20, $second->fresh()->sort_order);
    }

    /**
     * Rows render in ascending sort_order.
     *
     * Asserting the *labels* is not enough — they are numbered by display position, so "Bufet 1,
     * Bufet 2, Bufet 3" comes out right even when the rows are backwards. This pins which slot is
     * actually first, which is what a reversed sort breaks.
     */
    public function test_rows_render_in_ascending_order(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $date = $this->workday($team);

        $this->lockWeekOf($team, $date);

        $bufet = Position::factory()->create(['team_id' => $team->id, 'name' => 'Bufet', 'code' => null]);

        // Created out of order, so a sort that merely preserves insertion order fails too.
        $middle = PositionSlot::factory()->forPosition($bufet)->on($date)->create(['sort_order' => 20, 'start_time' => '12:30:00']);
        $last = PositionSlot::factory()->forPosition($bufet)->on($date)->create(['sort_order' => 30, 'start_time' => '13:00:00']);
        $first = PositionSlot::factory()->forPosition($bufet)->on($date)->create(['sort_order' => 10, 'start_time' => '12:00:00']);

        $rows = Livewire::actingAs($manager)
            ->test(RozpisDay::class, ['date' => $date])
            ->get('rows');

        $this->assertSame(
            [$first->id, $middle->id, $last->id],
            array_map(fn (array $row): int => $row['slot']->id, $rows),
        );

        $this->assertSame(['12:00', '12:30', '13:00'], array_column($rows, 'time'));
    }

    /** Ids from another day are ignored rather than trusted. */
    public function test_reordering_ignores_slots_from_another_day(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $date = $this->workday($team);
        $otherDay = CarbonImmutable::parse($date)->addDay()->toDateString();

        $this->lockWeekOf($team, $date);

        $position = Position::factory()->create(['team_id' => $team->id]);
        $mine = PositionSlot::factory()->forPosition($position)->on($date)->create(['sort_order' => 0]);
        $theirs = PositionSlot::factory()->forPosition($position)->on($otherDay)->create(['sort_order' => 0]);

        Livewire::actingAs($manager)
            ->test(RozpisDay::class, ['date' => $date])
            ->call('reorderSlots', [$theirs->id, $mine->id]);

        $this->assertSame(0, $theirs->fresh()->sort_order, 'A slot from another day must be left alone.');
        $this->assertSame(10, $mine->fresh()->sort_order);
    }

    /** A day inside the team's current week, so WeekService and the lock agree on which week it is. */
    private function workday(Team $team): string
    {
        return $this->weekStart($team)->addDay()->toDateString();
    }

    private function weekStart(Team $team): CarbonImmutable
    {
        return app(WeekService::class)->start($team, CarbonImmutable::now());
    }

    private function lockWeekOf(Team $team, string $date): void
    {
        $team->weekLocks()->create([
            'week_start' => app(WeekService::class)->start($team, CarbonImmutable::parse($date))->toDateString(),
        ]);
    }

    /** Pass a $slot to sign someone up already placed in it, as a manager would have left them. */
    private function signUp(Team $team, User $user, string $date, ?PositionSlot $slot = null): Assignment
    {
        return Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'position_id' => $slot?->position_id,
            'position_slot_id' => $slot?->getKey(),
            'date' => $date,
        ]);
    }
}
