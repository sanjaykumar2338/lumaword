<?php

namespace Database\Factories;

use App\Models\AiUsageReservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AiUsageReservation>
 */
class AiUsageReservationFactory extends Factory
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
            'feature' => 'ask_luma',
            'idempotency_key' => fake()->uuid(),
            'status' => 'reserved',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->addMonthNoOverflow()->startOfMonth(),
            'input_tokens' => 0,
            'output_tokens' => 0,
            'cost_micros' => 0,
        ];
    }
}
