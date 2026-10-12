<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SermonNoteResource extends JsonResource
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
            'summary' => $this->summary,
            'main_idea' => $this->main_idea,
            'outline' => $this->outline,
            'key_points' => $this->key_points,
            'scripture_references' => $this->scripture_references,
            'themes' => $this->themes,
            'applications' => $this->applications,
            'discussion_questions' => $this->discussion_questions,
            'ai' => [
                'provider' => $this->ai_provider,
                'model' => $this->ai_model,
                'prompt_version' => $this->prompt_version,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
