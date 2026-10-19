<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDevotionalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scripture_key' => ['required', 'string', Rule::in(array_keys(config('scripture.passages', [])))],
            'sermon_id' => [
                'nullable',
                'integer',
                Rule::exists('sermons', 'id')->where('user_id', $this->user()?->id),
            ],
        ];
    }
}
