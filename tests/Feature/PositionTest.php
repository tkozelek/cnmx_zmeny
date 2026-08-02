<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\PositionList;
use App\Models\Position;
use App\Models\PositionGroup;
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

    public function test_a_manager_creates_a_group_and_files_a_position_under_it(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);

        $component = Livewire::actingAs($manager)
            ->test(PositionList::class)
            ->set('groupName', 'Bufet')
            ->call('saveGroup')
            ->assertHasNoErrors();

        $group = PositionGroup::firstOrFail();
        $this->assertSame($team->id, $group->team_id);

        $component->set('name', 'Bufet 1')->set('groupId', $group->id)->call('save')->assertHasNoErrors();

        $this->assertDatabaseHas('positions', ['name' => 'Bufet 1', 'position_group_id' => $group->id]);
    }

    /**
     * Deleting a group unfiles its positions rather than taking them with it — the positions are
     * what assignments point at, and a heading is not worth losing history over.
     */
    public function test_deleting_a_group_keeps_its_positions(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);

        $group = PositionGroup::factory()->create(['team_id' => $team->id, 'name' => 'Bufet']);
        $position = Position::factory()->create(['team_id' => $team->id, 'position_group_id' => $group->id]);

        Livewire::actingAs($manager)
            ->test(PositionList::class)
            ->call('deleteGroup', $group->id)
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('position_groups', ['id' => $group->id]);
        $this->assertDatabaseHas('positions', ['id' => $position->id, 'position_group_id' => null]);
    }

    /**
     * Group order beats a position's own order, and ungrouped positions sort last — this is the
     * ordering the rozpis and the Excel both print, so it is worth pinning here.
     */
    public function test_positions_list_grouped_with_ungrouped_last(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);

        $uvadzac = PositionGroup::factory()->create(['team_id' => $team->id, 'name' => 'Uvádzač', 'sort_order' => 20]);
        $bufet = PositionGroup::factory()->create(['team_id' => $team->id, 'name' => 'Bufet', 'sort_order' => 10]);

        // Ordered so that a sort ignoring the group would produce the opposite list.
        Position::factory()->create(['team_id' => $team->id, 'name' => 'Nezaradená', 'sort_order' => 1]);
        Position::factory()->create(['team_id' => $team->id, 'name' => 'VIP', 'sort_order' => 2, 'position_group_id' => $uvadzac->id]);
        Position::factory()->create(['team_id' => $team->id, 'name' => 'Bufet 1', 'sort_order' => 3, 'position_group_id' => $bufet->id]);

        $listed = Livewire::actingAs($manager)
            ->test(PositionList::class)
            ->get('positions')
            ->pluck('name')
            ->all();

        $this->assertSame(['Bufet 1', 'VIP', 'Nezaradená'], $listed);
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
