<?php

namespace App\Http\Requests\Api\V1;

use App\Models\SermonCompanion;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSermonCompanionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $companion = $this->route('sermon_companion');

        return $companion instanceof SermonCompanion && $this->user()?->id === $companion->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['title' => ['sometimes', 'string', 'max:180'], 'speaker' => ['nullable', 'string', 'max:180'], 'church_name' => ['nullable', 'string', 'max:180'], 'sermon_date' => ['nullable', 'date'], 'youtube_url' => ['nullable', 'url', 'max:2000'], 'personal_notes' => ['nullable', 'string', 'max:100000'], 'scripture_references' => ['nullable', 'array', 'max:100'], 'scripture_references.*' => ['string', 'max:200'], 'key_takeaways' => ['nullable', 'array', 'max:30'], 'key_takeaways.*' => ['string', 'max:2000'], 'personal_reflections' => ['nullable', 'string', 'max:20000'], 'confirmed_notes' => ['nullable', 'string', 'max:120000']];
    }
}
