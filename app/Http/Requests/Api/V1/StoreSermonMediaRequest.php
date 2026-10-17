<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Sermon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreSermonMediaRequest extends FormRequest
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
            'media' => [
                'required',
                File::types(config('sermons.upload_extensions'))->max(config('sermons.limits.maximum_upload_kilobytes')),
            ],
        ];
    }
}
