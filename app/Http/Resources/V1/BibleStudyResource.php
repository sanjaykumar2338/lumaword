<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BibleStudyResource extends JsonResource
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
            'title' => $this->title,
            'scripture_key' => $this->scripture_key,
            'scripture_reference' => $this->scripture_reference,
            'translation' => $this->translation,
            'scripture_text' => $this->scripture_text,
            'depth' => $this->depth->value,
            'depth_label' => $this->depth->label(),
            'content' => $this->content,
            'personal_notes' => $this->personal_notes,
            'ai' => [
                'provider' => $this->ai_provider,
                'model' => $this->ai_model,
                'prompt_version' => $this->prompt_version,
            ],
            'generated_at' => $this->generated_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
