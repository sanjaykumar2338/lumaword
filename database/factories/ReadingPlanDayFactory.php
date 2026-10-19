<?php

namespace Database\Factories;

use App\Models\ReadingPlan;
use App\Models\ReadingPlanDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingPlanDay>
 */
class ReadingPlanDayFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reading_plan_id' => ReadingPlan::factory(),
            'day_number' => 1,
            'title' => fake()->sentence(3),
            'scripture_key' => 'psalm-23',
            'scripture_reference' => 'Psalm 23:1-6',
        ];
    }
}
