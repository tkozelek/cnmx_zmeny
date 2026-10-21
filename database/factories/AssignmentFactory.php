<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Position;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    /**
     * `position_id` is null by default, matching self-signup: the employee picks a day, an
     * admin fills the position in later. Use `onPosition()` when a position matters.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'position_id' => null,
            'date' => fake()->dateTimeBetween('-2 weeks', '+2 weeks')->format('Y-m-d'),
            'created_by' => null,
        ];
    }

    /**
     * Attaches a position *and* takes the team from it. Two independent factories would
     * produce two different teams, and the (team_id, position_id) composite foreign key
     * correctly rejects that pairing - which makes for a confusing test failure.
     */
    public function onPosition(?Position $position = null): static
    {
        $position ??= Position::factory()->create();

        return $this->state([
            'position_id' => $position->id,
            'team_id' => $position->team_id,
        ]);
    }

    /** Placed by an admin rather than self-signup. */
    public function assignedBy(User $admin): static
    {
        return $this->state(['created_by' => $admin->id]);
    }
}
