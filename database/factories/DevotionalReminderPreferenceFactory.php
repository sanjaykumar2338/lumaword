<?php

namespace Database\Factories;

use App\Models\DevotionalReminderPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DevotionalReminderPreference>
 */
class DevotionalReminderPreferenceFactory extends Factory
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
            'enabled' => true,
            'reminder_time' => '08:00',
            'timezone' => 'UTC',
        ];
    }
}
