<?php

namespace Database\Factories;

use App\Models\Material;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'material_id' => Material::factory(),
            'project_id' => Project::factory(),
            'quantity' => fake()->numberBetween(50, 1_000),
            'allocated_quantity' => 0,
            'used_quantity' => 0,
            'location' => fake()->randomElement(['Warehouse A', 'Site Store', 'Yard 1']),
            'status' => 'available',
        ];
    }
}
