<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SermonCompanionDevotionalPlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'days_count' => $this->days_count, 'status' => $this->status, 'days' => SermonCompanionDevotionalDayResource::collection($this->whenLoaded('days')), 'progress' => $this->whenLoaded('progress'), 'generated_at' => $this->generated_at?->toISOString(), 'completed_at' => $this->completed_at?->toISOString()];
    }
}
