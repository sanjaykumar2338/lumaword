<?php

namespace Database\Factories;

use App\Models\SermonConversation;
use App\Models\SermonMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SermonMessage>
 */
class SermonMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => SermonConversation::factory(),
            'role' => SermonMessage::ROLE_USER,
            'content' => fake()->sentence(),
            'status' => SermonMessage::STATUS_COMPLETED,
            'client_request_id' => fake()->uuid(),
        ];
    }

    public function assistant(): static
    {
        return $this->state(fn (): array => [
            'role' => SermonMessage::ROLE_ASSISTANT,
            'client_request_id' => null,
            'ai_provider' => 'openai',
            'ai_model' => 'test-model',
        ]);
    }
}
