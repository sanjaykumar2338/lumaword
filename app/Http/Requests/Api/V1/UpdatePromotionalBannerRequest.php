<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePromotionalBannerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:1000'],
            'image_url' => ['sometimes', 'nullable', 'url:https', 'max:2048'],
            'image_alt' => ['sometimes', 'nullable', 'required_with:image_url', 'string', 'max:255'],
            'destination_url' => ['sometimes', 'required', 'url:https', 'max:2048'],
            'placement' => ['sometimes', 'required', 'in:dashboard,sermon_library,settings'],
            'is_enabled' => ['sometimes', 'required', 'boolean'],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'nullable', 'date', 'after:starts_at'],
        ];
    }
}
