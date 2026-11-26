<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'sermon_processing' => ['required', 'boolean'],
            'devotional_reminders' => ['required', 'boolean'],
            'subscription_updates' => ['required', 'boolean'],
            'reading_plan_reminders' => ['sometimes', 'boolean'],
            'reminder_time' => ['sometimes', 'date_format:H:i'],
            'timezone' => ['sometimes', 'string', 'timezone:all'],
        ];
    }
}
