<?php

namespace Database\Factories;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PushSubscription>
 */
class PushSubscriptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $endpoint = 'https://push.example.test/'.fake()->uuid();

        return [
            'user_id' => User::factory(),
            'endpoint_hash' => hash('sha256', $endpoint),
            'endpoint' => $endpoint,
            'public_key' => fake()->regexify('[A-Za-z0-9_-]{87}'),
            'auth_token' => fake()->regexify('[A-Za-z0-9_-]{22}'),
            'content_encoding' => 'aes128gcm',
        ];
    }
}
