<?php

namespace App\Http\Requests\Api\V1;

use App\BibleStudyDepth;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
                'required',
                'string',
                Rule::in(array_keys(config('scripture.passages', []))),
            ],
            'depth' => ['required', Rule::enum(BibleStudyDepth::class)],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'scripture_key.in' => 'The selected passage is not available in the approved Scripture catalog.',
            'depth.enum' => 'Choose Quick Read, Go Deeper, or Study in Depth.',
        ];
    }
}
