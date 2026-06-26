<?php

namespace Database\Factories;

use App\Models\Phase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'phase_id' => Phase::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'status' => 'pending',
            'order' => fake()->numberBetween(1, 10),
            'weight' => fake()->numberBetween(10, 50),
            'estimated_cost' => fake()->numberBetween(1_000, 50_000),
            'estimated_hours' => fake()->numberBetween(8, 200),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'actual_end_date' => now(),
        ]);
    }
}
