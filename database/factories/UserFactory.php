<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => $this->faker->firstName,
            'lastname' => $this->faker->lastName,
            'email' => $this->faker->unique()->safeEmail,
            'password' => bcrypt('password'), // You can use bcrypt() to hash the password
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
            'id_role' => $this->faker->numberBetween(1, 4), // Assuming there are 5 roles
        ];
    }
}
