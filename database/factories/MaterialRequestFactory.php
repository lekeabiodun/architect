<?php

namespace Database\Factories;

use App\Models\BillOfQuantity;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MaterialRequest>
 */
class MaterialRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            // Keep the BOQ item under the same project as the request.
            'bill_of_quantity_id' => fn (array $attributes) => BillOfQuantity::factory()
                ->create(['project_id' => $attributes['project_id']])
                ->id,
            'requested_quantity' => fake()->numberBetween(1, 50),
            'required_date' => now()->addWeek(),
            'purpose' => fake()->sentence(),
            'justification' => fake()->sentence(),
            'requested_by' => User::factory(),
            'status' => 'pending',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }
}
