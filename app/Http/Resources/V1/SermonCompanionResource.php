<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SermonCompanionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'title' => $this->title, 'speaker' => $this->speaker, 'church_name' => $this->church_name, 'sermon_date' => $this->sermon_date?->toDateString(), 'youtube_url' => $this->youtube_url, 'personal_notes' => $this->personal_notes, 'scripture_references' => $this->scripture_references ?? [], 'key_takeaways' => $this->key_takeaways ?? [], 'personal_reflections' => $this->personal_reflections, 'confirmed_notes' => $this->confirmed_notes, 'recap' => $this->recap, 'status' => $this->status, 'processing_error' => $this->processing_error, 'attachments' => $this->whenLoaded('attachments'), 'devotional_plans' => $this->whenLoaded('devotionalPlans'), 'created_at' => $this->created_at?->toISOString(), 'updated_at' => $this->updated_at?->toISOString()];
    }
}
