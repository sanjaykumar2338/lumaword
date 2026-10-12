<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Sermon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSermonRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:180'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['title' => trim($this->string('title')->toString())]);
    }
}
