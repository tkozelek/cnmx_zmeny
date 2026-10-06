<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'date' => fake()->dateTimeBetween('-2 weeks', '+2 weeks')->format('Y-m-d'),
            'created_by' => null,
        ];
    }

    /** Placed by an admin rather than self-signup. */
    public function assignedBy(User $admin): static
    {
        return $this->state(['created_by' => $admin->id]);
    }
}
