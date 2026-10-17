<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\YoutubeConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<YoutubeConnection>
 */
class YoutubeConnectionFactory extends Factory
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
            'channel_id' => 'UC'.fake()->regexify('[A-Za-z0-9_-]{22}'),
            'channel_title' => fake()->company(),
            'access_token' => fake()->sha256(),
            'refresh_token' => fake()->sha256(),
            'token_expires_at' => now()->addHour(),
            'scopes' => ['https://www.googleapis.com/auth/youtube.force-ssl'],
        ];
    }
}
