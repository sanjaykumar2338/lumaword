<?php

namespace App\Http\Requests\Api\V1;

use App\Models\SermonCompanion;
use Illuminate\Foundation\Http\FormRequest;

class StoreSermonCompanionJournalEntryRequest extends FormRequest
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
        return ['devotional_day_id' => ['nullable', 'integer', 'exists:sermon_companion_devotional_days,id'], 'entry_type' => ['required', 'in:reflection,journal,prayer,application,midweek_check_in'], 'client_key' => ['nullable', 'string', 'max:100'], 'content' => ['required', 'string', 'max:30000']];
    }
}
