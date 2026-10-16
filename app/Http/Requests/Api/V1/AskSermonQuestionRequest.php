<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Sermon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AskSermonQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $sermon = $this->route('sermon');

        return $sermon instanceof Sermon && ($this->user()?->can('view', $sermon) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'max:'.config('sermons.qa.maximum_question_characters')],
            'client_request_id' => ['required', 'uuid'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['question' => trim($this->string('question')->toString())]);
    }
}
