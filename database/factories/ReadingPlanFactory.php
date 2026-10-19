<?php

namespace Database\Factories;

use App\Models\ReadingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingPlan>
 */
class ReadingPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(3),
            'title' => fake()->sentence(3),
            'description' => fake()->sentence(12),
            'duration_days' => 7,
            'is_published' => true,
        ];
    }
}
