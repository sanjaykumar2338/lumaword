<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\SafeSermonUrl;
use App\SermonSourceType;
use App\Services\SermonUrlService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class StoreSermonRequest extends FormRequest
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
            'source_type' => ['required', Rule::enum(SermonSourceType::class)],
            'title' => ['nullable', 'string', 'max:180'],
            'source_url' => [
                Rule::requiredIf($this->string('source_type')->toString() === SermonSourceType::Url->value),
                'nullable',
                new SafeSermonUrl(new SermonUrlService),
            ],
            'transcript' => [
                Rule::requiredIf($this->string('source_type')->toString() === SermonSourceType::Transcript->value),
                'nullable',
                'string',
                'min:'.config('sermons.limits.minimum_transcript_characters'),
                'max:'.config('sermons.limits.maximum_transcript_characters'),
            ],
            'media' => [
                Rule::requiredIf($this->string('source_type')->toString() === SermonSourceType::Upload->value),
                'nullable',
                File::types(config('sermons.upload_extensions'))->max(config('sermons.limits.maximum_upload_kilobytes')),
            ],
            'transcript_language' => ['nullable', 'string', 'max:12'],
            'auto_process' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => $this->filled('title') ? trim($this->string('title')->toString()) : null,
            'source_url' => $this->filled('source_url') ? trim($this->string('source_url')->toString()) : null,
            'transcript' => $this->filled('transcript') ? trim($this->string('transcript')->toString()) : null,
        ]);
    }
}
