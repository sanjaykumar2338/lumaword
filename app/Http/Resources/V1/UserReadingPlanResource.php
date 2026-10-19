<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserReadingPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'completed_days' => $this->whenCounted('progress'),
            'plan' => new ReadingPlanResource($this->whenLoaded('readingPlan')),
            'completed_day_ids' => $this->whenLoaded(
                'progress',
                fn () => $this->progress->pluck('reading_plan_day_id')->values(),
            ),
        ];
    }
}
