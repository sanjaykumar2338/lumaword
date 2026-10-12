<?php

namespace Database\Factories;

use App\Models\Sermon;
use App\Models\SermonNote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SermonNote>
 */
class SermonNoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sermon_id' => Sermon::factory(),
            'summary' => fake()->paragraph(),
            'main_idea' => fake()->sentence(),
            'outline' => [['heading' => 'Opening', 'details' => 'The sermon introduction.']],
            'key_points' => ['Trust God in ordinary faithfulness.'],
            'scripture_references' => [],
            'themes' => ['faithfulness'],
            'applications' => ['Choose one faithful next step this week.'],
            'discussion_questions' => ['What faithful next step will you take?'],
            'ai_provider' => 'openai',
            'ai_model' => 'test-model',
            'prompt_version' => 'sermon-notes-v1',
        ];
    }
}
