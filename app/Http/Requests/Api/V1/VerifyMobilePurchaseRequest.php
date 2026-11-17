<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyMobilePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'store' => ['required', 'string', Rule::in(['app_store', 'play_store'])],
            'product_id' => ['required', 'string', Rule::in(['luma_monthly', 'luma_annual'])],
            'transaction_id' => ['required', 'string', 'max:255'],
            'verification_data' => ['required', 'string', 'max:100000'],
        ];
    }
}
