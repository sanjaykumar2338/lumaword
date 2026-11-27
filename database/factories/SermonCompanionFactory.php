<?php

namespace Database\Factories;

use App\Models\SermonCompanion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SermonCompanion>
 */
class SermonCompanionFactory extends Factory
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
            'title' => fake()->sentence(5),
            'status' => 'awaiting_review',
            'personal_notes' => fake()->paragraph(),
            'scripture_references' => ['John 3:16'],
        ];
    }
}
