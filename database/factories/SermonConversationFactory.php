<?php

namespace Database\Factories;

use App\Models\Sermon;
use App\Models\SermonConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SermonConversation>
 */
class SermonConversationFactory extends Factory
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
            'sermon_id' => Sermon::factory(),
            'title' => fake()->sentence(6),
        ];
    }
}
