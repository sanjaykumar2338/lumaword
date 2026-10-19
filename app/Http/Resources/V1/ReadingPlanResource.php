<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReadingPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $enrollment = $this->relationLoaded('enrollments') ? $this->enrollments->first() : null;

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->title,
            'description' => $this->description,
            'duration_days' => $this->duration_days,
            'days' => $this->whenLoaded('days', fn () => $this->days->map(function ($day): array {
                $scripture = config("scripture.passages.{$day->scripture_key}");

                return [
                    'id' => $day->id,
                    'day_number' => $day->day_number,
                    'title' => $day->title,
                    'scripture_key' => $day->scripture_key,
                    'scripture_reference' => $day->scripture_reference,
                    'translation' => $scripture['translation'],
                    'scripture_text' => $scripture['text'],
                ];
            })),
            'enrollment' => $enrollment === null ? null : [
                'id' => $enrollment->id,
                'started_at' => $enrollment->started_at?->toISOString(),
                'completed_at' => $enrollment->completed_at?->toISOString(),
                'completed_day_ids' => $enrollment->relationLoaded('progress')
                    ? $enrollment->progress->pluck('reading_plan_day_id')->values()
                    : [],
            ],
        ];
    }
}
