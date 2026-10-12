<?php

namespace App\Rules;

use App\Services\SermonUrlService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class SafeSermonUrl implements ValidationRule
{
    public function __construct(private readonly SermonUrlService $urlService) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a valid URL.');

            return;
        }

        try {
            $this->urlService->normalize($value);
        } catch (\InvalidArgumentException $exception) {
            $fail($exception->getMessage());
        }
    }
}
