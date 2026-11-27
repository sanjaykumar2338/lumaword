<?php

namespace App\Http\Requests\Api\V1;

use App\Models\SermonCompanionMemoryCard;
use Illuminate\Foundation\Http\FormRequest;

class StoreSermonCompanionMemoryPracticeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $card = $this->route('memory_card');

        return $card instanceof SermonCompanionMemoryCard && $this->user()?->id === $card->companion->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['completed' => ['required', 'boolean']];
    }
}
