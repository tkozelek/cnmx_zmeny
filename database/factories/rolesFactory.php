<?php

namespace Database\Factories;

use App\Models\roles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class rolesFactory extends Factory
{
    protected $model = roles::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'guard_name' => $this->faker->name(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
