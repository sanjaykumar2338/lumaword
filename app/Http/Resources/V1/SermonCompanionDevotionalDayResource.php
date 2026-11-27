<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SermonCompanionDevotionalDayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return ['id' => $this->id, 'day_number' => $this->day_number, 'title' => $this->title, 'theme' => $this->theme, 'scripture_reference' => $this->scripture_reference, 'translation' => $this->translation, 'scripture_text' => $this->scripture_text, 'reflection' => $this->reflection, 'reflection_question' => $this->reflection_question, 'practical_application' => $this->practical_application, 'prayer' => $this->prayer, 'completed_at' => $this->completed_at?->toISOString()];
    }
}
