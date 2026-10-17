<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SermonResource extends JsonResource
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
            'source_type' => $this->source_type->value,
            'source_url' => $this->source_url,
            'source_provider' => $this->source_provider,
            'provider_video_id' => $this->provider_video_id,
            'processing_status' => $this->processing_status->value,
            'processing_error' => $this->processing_error,
            'transcript' => $this->when($this->resource->hasAttribute('transcript'), $this->transcript),
            'has_transcript' => $this->resource->hasAttribute('has_transcript')
                ? (bool) $this->has_transcript
                : filled($this->transcript),
            'transcript_source' => $this->transcript_source->value,
            'transcript_language' => $this->transcript_language,
            'duration_seconds' => $this->duration_seconds,
            'processing_started_at' => $this->processing_started_at?->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),
            'notes' => new SermonNoteResource($this->whenLoaded('notes')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
