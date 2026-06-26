<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Material>
 */
class MaterialFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true),
            'code' => strtoupper(fake()->unique()->bothify('MAT-####')),
            'description' => fake()->sentence(),
            'unit' => fake()->randomElement(['kg', 'm3', 'pieces', 'liters']),
            'unit_cost' => fake()->numberBetween(5, 2_000),
            'currency' => 'USD',
            'category' => fake()->randomElement(['cement', 'steel', 'lumber', 'electrical']),
            'reorder_level' => fake()->numberBetween(10, 100),
        ];
    }
}
