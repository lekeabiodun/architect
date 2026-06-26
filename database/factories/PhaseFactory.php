<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Phase>
 */
class PhaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => fake()->randomElement(['Foundation', 'Framing', 'Roofing', 'Finishing']),
            'description' => fake()->sentence(),
            'order' => fake()->numberBetween(1, 10),
            'weight' => fake()->numberBetween(10, 40),
            'status' => 'pending',
        ];
    }
}
