<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\PositionList;
use App\Models\Position;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The position catalogue. Before this existed the only way to add a position was editing the
 * seeder, which is why it is a prerequisite for the rozpis builder rather than a nice-to-have.
 */
class PositionTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_manager_creates_a_position(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);

        Livewire::actingAs($manager)
            ->test(PositionList::class)
            ->set('name', 'Bufet')
            ->set('code', 'BUF')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertDatabaseHas('positions', [
            'team_id' => $team->id,
            'name' => 'Bufet',
            'code' => 'BUF',
            'is_active' => true,
        ]);
    }

    /** Two cinemas must both be able to have a "Bufet"; one cinema must not have two. */
    public function test_a_duplicate_name_within_the_team_is_rejected(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);

        Position::factory()->create(['team_id' => $team->id, 'name' => 'Bufet']);

        Livewire::actingAs($manager)
            ->test(PositionList::class)
            ->set('name', 'Bufet')
            ->call('save')
            ->assertHasErrors('name');

        $this->assertSame(1, Position::where('name', 'Bufet')->count());
    }

    public function test_an_employee_cannot_create_a_position(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);

        Livewire::actingAs($employee)
            ->test(PositionList::class)
            ->set('name', 'Bufet')
            ->call('save')
            ->assertForbidden();

        $this->assertDatabaseCount('positions', 0);
    }

    public function test_the_catalogue_page_is_closed_to_employees(): void
    {
        $team = $this->tenant();

        $this->actingAs($this->member($team))
            ->get(route('positions.index'))
            ->assertForbidden();

        $this->actingAs($this->member($team, Role::Manager))
            ->get(route('positions.index'))
            ->assertOk();
    }

    /**
     * Moving re-indexes the whole list, so seeded rows sharing sort_order 0 still end up in a
     * definite order instead of silently not moving.
     */
    public function test_moving_a_position_reorders_the_catalogue(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);

        $first = Position::factory()->create(['team_id' => $team->id, 'name' => 'A', 'sort_order' => 0]);
        $second = Position::factory()->create(['team_id' => $team->id, 'name' => 'B', 'sort_order' => 0]);

        Livewire::actingAs($manager)
            ->test(PositionList::class)
            ->call('move', $second->id, -1);

        $this->assertTrue(
            $second->fresh()->sort_order < $first->fresh()->sort_order,
            'B was moved up, so it must now sort before A.'
        );
    }

    /**
     * Deactivating rather than deleting: the composite FK on `assignments` cascades, so deleting
     * a position would take its history with it.
     */
    public function test_deactivating_keeps_the_position_and_its_history(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $position = Position::factory()->create(['team_id' => $team->id]);

        Livewire::actingAs($manager)
            ->test(PositionList::class)
            ->call('toggleActive', $position->id);

        $this->assertDatabaseHas('positions', ['id' => $position->id, 'is_active' => false]);
        $this->assertSame(0, Position::selectable()->count(), 'An inactive position is no longer offered.');
    }
}
