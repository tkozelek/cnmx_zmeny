<?php

namespace Database\Factories;

use App\Models\BugReport;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BugReport>
 */
class BugReportFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->safeEmail(),
            'description' => fake()->sentence(12),
            'page' => '/tyzdne',
            'user_agent' => 'Mozilla/5.0',
            'attachments' => [],
        ];
    }
}
