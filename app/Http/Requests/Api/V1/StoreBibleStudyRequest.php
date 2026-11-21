<?php

namespace App\Http\Requests\Api\V1;

use App\BibleStudyDepth;
use App\Services\BibleReaderService;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBibleStudyRequest extends FormRequest
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
            'scripture_key' => [
                'nullable',
                'string',
                Rule::in(array_keys(config('scripture.passages', []))),
                'required_without:reader_reference',
            ],
            'reader_reference' => ['nullable', 'array', 'required_without:scripture_key'],
            'reader_reference.translation' => ['required_with:reader_reference', 'string', Rule::in(array_keys(config('bible.translations', [])))],
            'reader_reference.book' => ['required_with:reader_reference', 'string', 'max:32'],
            'reader_reference.chapter' => ['required_with:reader_reference', 'integer', 'min:1'],
            'reader_reference.verse_start' => ['nullable', 'integer', 'min:1'],
            'reader_reference.verse_end' => ['nullable', 'integer', 'gte:reader_reference.verse_start'],
            'depth' => ['required', Rule::enum(BibleStudyDepth::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'scripture_key.in' => 'The selected passage is not available in the approved Scripture catalog.',
            'scripture_key.required_without' => 'Choose a catalog passage or a Bible Reader passage.',
            'reader_reference.required_without' => 'Choose a catalog passage or a Bible Reader passage.',
            'depth.enum' => 'Choose Quick Read, Go Deeper, or Study in Depth.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $reference = $this->input('reader_reference');
            if (! is_array($reference)
                || ! is_string($reference['translation'] ?? null)
                || ! is_string($reference['book'] ?? null)
                || ! is_numeric($reference['chapter'] ?? null)) {
                return;
            }

            try {
                $reader = app(BibleReaderService::class);
                $bookId = $reader->resolveBookId($reference['translation'], $reference['book']);
                $reader->passage(
                    $reference['translation'],
                    $bookId,
                    (int) $reference['chapter'],
                    isset($reference['verse_start']) ? (int) $reference['verse_start'] : null,
                    isset($reference['verse_end']) ? (int) $reference['verse_end'] : null,
                );
            } catch (\InvalidArgumentException $exception) {
                $validator->errors()->add('reader_reference', $exception->getMessage());
            }
        });
    }
}
