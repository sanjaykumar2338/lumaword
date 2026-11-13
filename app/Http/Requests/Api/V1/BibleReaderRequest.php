<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BibleReaderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'translation' => ['nullable', 'string', Rule::in(array_keys(config('bible.translations', [])))],
            'book' => ['nullable', 'string', 'max:8'],
            'chapter' => ['nullable', 'integer', 'min:1'],
            'verse_start' => ['nullable', 'integer', 'min:1'],
            'verse_end' => ['nullable', 'integer', 'gte:verse_start'],
            'q' => ['nullable', 'string', 'max:200'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
