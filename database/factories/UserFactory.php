<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'lastname' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'remember_token' => Str::random(10),
            'is_active' => true,
            'email_verified_at' => now(),
        ];
    }

    /** Registered but has not clicked the link yet, so no manager may approve them. */
    public function unverified(): static
    {
        return $this->state(fn (): array => ['email_verified_at' => null]);
    }

    /** Blocked - the state the legacy "zablokovany" role stood for. */
    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    /** An approved member of $team, acting in it. */
    public function memberOf(Team $team, ?string $role = null): static
    {
        return $this->state(['current_team_id' => $team->id])
            ->afterCreating(function (User $user) use ($team, $role): void {
                $user->teams()->attach($team, ['approved_at' => now()]);

                if ($role) {
                    setPermissionsTeamId($team->id);
                    $user->assignRole($role);
                }
            });
    }

    /** Signed up but not yet let in - the legacy "neovereny" role. */
    public function pendingIn(Team $team): static
    {
        return $this->afterCreating(function (User $user) use ($team): void {
            $user->teams()->attach($team, ['approved_at' => null]);
        });
    }
}
