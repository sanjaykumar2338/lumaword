<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SermonCompanionMemoryCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'reference' => $this->reference, 'translation' => $this->translation, 'verse_text' => $this->verse_text, 'fill_in_blank' => $this->fill_in_blank, 'correct_answers' => $this->correct_answers, 'practice' => $this->whenLoaded('practice')];
    }
}
