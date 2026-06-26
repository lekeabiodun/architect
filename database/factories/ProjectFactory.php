<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Project>
 */
class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company().' Project',
            'description' => fake()->sentence(),
            'location' => fake()->city(),
            'status' => 'active',
            'planned_start_date' => now()->subDays(10),
            'planned_end_date' => now()->addMonths(6),
            'estimated_budget' => fake()->numberBetween(100_000, 5_000_000),
            'currency' => 'USD',
            'manager_id' => User::factory(),
        ];
    }

    /**
     * Assign a specific user as the project manager.
     */
    public function managedBy(User $user): static
    {
        return $this->state(fn () => ['manager_id' => $user->id]);
    }
}
