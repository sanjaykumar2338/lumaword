<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DevotionalResource extends JsonResource
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
            'sermon_id' => $this->sermon_id,
            'source_type' => $this->source_type,
            'title' => $this->title,
            'scripture_key' => $this->scripture_key,
            'scripture_reference' => $this->scripture_reference,
            'translation' => $this->translation,
            'scripture_text' => $this->scripture_text,
            'reflection' => $this->reflection,
            'application' => $this->application,
            'prayer' => $this->prayer,
            'is_ai_generated' => true,
            'generated_at' => $this->generated_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
