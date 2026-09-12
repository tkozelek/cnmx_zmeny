<?php

namespace Database\Factories;

use App\Models\Absence;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Absence>
 */
class AbsenceFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $from = fake()->dateTimeBetween('+3 days', '+3 weeks');

        return [
            'team_id' => Team::factory(),
            'user_id' => User::factory(),
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $from->format('Y-m-d'),
            'day_of_week' => null,
            'reason' => fake()->optional()->sentence(4),
        ];
    }

    /** "Every Tuesday from now until further notice." */
    public function recurring(int $dayOfWeek = 1): static
    {
        return $this->state([
            'date_from' => now()->toDateString(),
            'date_to' => Absence::FOREVER,
            'day_of_week' => $dayOfWeek,
        ]);
    }

    public function past(): static
    {
        return $this->state([
            'date_from' => now()->subMonth()->toDateString(),
            'date_to' => now()->subWeeks(3)->toDateString(),
        ]);
    }
}
