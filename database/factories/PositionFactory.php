<?php

namespace Database\Factories;

use App\Models\Position;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->unique()->jobTitle(),
            'code' => Str::upper(fake()->unique()->lexify('??')),
            'is_manager' => false,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }

    public function manager(): static
    {
        return $this->state(['name' => 'Vedúci', 'code' => 'VED', 'is_manager' => true]);
    }
}
