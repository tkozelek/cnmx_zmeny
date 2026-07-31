<?php

namespace Database\Factories;

use App\Models\Position;
use App\Models\PositionSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PositionSlot>
 */
class PositionSlotFactory extends Factory
{
    /**
     * The team comes from the position, never from a second Team factory — the
     * (team_id, position_id) composite foreign key correctly rejects a mismatched pair, and
     * that reads as a baffling test failure. Same reasoning as AssignmentFactory::onPosition().
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $position = Position::factory()->create();

        return [
            'position_id' => $position->id,
            'team_id' => $position->team_id,
            'date' => fake()->dateTimeBetween('-1 week', '+2 weeks')->format('Y-m-d'),
            'start_time' => '16:00:00',
            'end_time' => null,
        ];
    }

    public function forPosition(Position $position): static
    {
        return $this->state([
            'position_id' => $position->id,
            'team_id' => $position->team_id,
        ]);
    }

    public function on(string $date): static
    {
        return $this->state(['date' => $date]);
    }
}
