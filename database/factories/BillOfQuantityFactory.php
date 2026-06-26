<?php

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BillOfQuantity>
 */
class BillOfQuantityFactory extends Factory
{
    public function definition(): array
    {
        $quantity = fake()->numberBetween(10, 500);
        $unitRate = fake()->numberBetween(5, 1_000);

        return [
            'project_id' => Project::factory(),
            'item_code' => strtoupper(fake()->bothify('BOQ-###')),
            'description' => fake()->words(3, true),
            'unit' => fake()->randomElement(['bag', 'ton', 'm3', 'piece', 'litre']),
            'quantity' => $quantity,
            'unit_rate' => $unitRate,
            'total_amount' => $quantity * $unitRate,
            'requestable_quantity' => $quantity,
            'consumed_quantity' => 0,
            'category' => fake()->randomElement(['cement', 'steel', 'lumber', 'electrical']),
            'order' => fake()->numberBetween(1, 20),
        ];
    }
}
