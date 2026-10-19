<?php

namespace Database\Factories;

use App\Models\ReadingPlan;
use App\Models\User;
use App\Models\UserReadingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserReadingPlan>
 */
class UserReadingPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'reading_plan_id' => ReadingPlan::factory(),
            'started_at' => now(),
            'completed_at' => null,
        ];
    }
}
