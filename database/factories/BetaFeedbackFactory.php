<?php

namespace Database\Factories;

use App\Models\BetaFeedback;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BetaFeedback>
 */
class BetaFeedbackFactory extends Factory
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
            'category' => fake()->randomElement(['bug', 'suggestion', 'usability', 'other']),
            'message' => fake()->paragraph(),
        ];
    }
}
