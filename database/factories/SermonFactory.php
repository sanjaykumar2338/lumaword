<?php

namespace Database\Factories;

use App\Models\Sermon;
use App\Models\User;
use App\SermonProcessingStatus;
use App\SermonSourceType;
use App\TranscriptSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sermon>
 */
class SermonFactory extends Factory
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
            'source_type' => SermonSourceType::Transcript,
            'processing_status' => SermonProcessingStatus::TranscriptReady,
            'transcript' => fake()->paragraphs(8, true),
            'transcript_source' => TranscriptSource::Pasted,
        ];
    }

    public function failed(): static
    {
        return $this->state(fn (): array => [
            'processing_status' => SermonProcessingStatus::Failed,
            'processing_error' => 'Processing could not be completed.',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (): array => [
            'processing_status' => SermonProcessingStatus::Completed,
            'processed_at' => now(),
        ]);
    }
}
