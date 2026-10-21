<?php

namespace Database\Factories;

use App\Models\DevotionalReminderDelivery;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevotionalReminderDelivery>
 */
class DevotionalReminderDeliveryFactory extends Factory
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
            'reminder_date' => today(),
            'sent_at' => null,
        ];
    }
}
