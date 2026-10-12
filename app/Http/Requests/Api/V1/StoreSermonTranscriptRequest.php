<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Sermon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSermonTranscriptRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $sermon = $this->route('sermon');

        return $sermon instanceof Sermon && ($this->user()?->can('update', $sermon) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'transcript' => [
                'required',
                'string',
                'min:'.config('sermons.limits.minimum_transcript_characters'),
                'max:'.config('sermons.limits.maximum_transcript_characters'),
            ],
            'transcript_language' => ['nullable', 'string', 'max:12'],
            'process' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['transcript' => trim($this->string('transcript')->toString())]);
    }
}
