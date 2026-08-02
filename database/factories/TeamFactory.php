<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\TeamSetting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = 'Kino '.fake()->unique()->city();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
        ];
    }

    /**
     * Every team gets its settings row immediately - this is Laravel's own factory hook, so
     * it runs for every `Team::factory()` with nothing to remember at the call site.
     *
     * It matters because `Team::weekStartDay()` falls back to a default when settings are
     * missing: a team without the row would silently behave as though it had been
     * configured, which is exactly the bug that never shows up in a test.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Team $team): void {
            TeamSetting::withoutGlobalScope('team')->create(['team_id' => $team->id]);
        });
    }
}
