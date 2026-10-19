<?php

namespace Database\Factories;

use App\Models\ReadingPlanDay;
use App\Models\ReadingPlanProgress;
use App\Models\UserReadingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingPlanProgress>
 */
class ReadingPlanProgressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_reading_plan_id' => UserReadingPlan::factory(),
            'reading_plan_day_id' => ReadingPlanDay::factory(),
            'completed_at' => now(),
        ];
    }
}
