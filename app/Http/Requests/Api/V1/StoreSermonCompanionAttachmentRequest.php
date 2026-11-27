<?php

namespace App\Http\Requests\Api\V1;

use App\Models\SermonCompanion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreSermonCompanionAttachmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $companion = $this->route('sermon_companion');

        return $companion instanceof SermonCompanion && $this->user()?->id === $companion->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['kind' => ['required', 'in:image,audio'], 'file' => ['required', File::types($this->string('kind')->toString() === 'image' ? ['jpg', 'jpeg', 'png', 'webp'] : ['mp3', 'm4a', 'wav', 'aac'])->max((int) config('sermon_companion.upload.max_kb', 25600))]];
    }
}
