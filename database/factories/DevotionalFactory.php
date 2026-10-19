<?php

namespace Database\Factories;

use App\Models\Devotional;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Devotional>
 */
class DevotionalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scripture = config('scripture.passages.psalm-23');

        return [
            'user_id' => User::factory(),
            'source_type' => 'passage',
            'title' => fake()->sentence(4),
            'scripture_key' => 'psalm-23',
            'scripture_reference' => $scripture['reference'],
            'translation' => $scripture['translation'],
            'scripture_text' => $scripture['text'],
            'reflection' => fake()->paragraph(5),
            'application' => fake()->sentence(12),
            'prayer' => fake()->paragraph(3),
            'ai_provider' => 'openai',
            'ai_model' => 'test-model',
            'prompt_version' => 'devotional-v1',
            'generated_at' => now(),
        ];
    }
}
