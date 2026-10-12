<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SermonNotesSchema
{
    /**
     * @return array<string, mixed>
     */
    public function schema(): array
    {
        $stringArray = [
            'type' => 'array',
            'items' => ['type' => 'string'],
        ];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'properties' => [
                'title' => ['type' => 'string'],
                'main_idea' => ['type' => 'string'],
                'summary' => ['type' => 'string'],
                'outline' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'heading' => ['type' => 'string'],
                            'details' => ['type' => 'string'],
                        ],
                        'required' => ['heading', 'details'],
                    ],
                ],
                'key_points' => $stringArray,
                'scripture_references' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'properties' => [
                            'reference' => ['type' => 'string'],
                            'explicitly_mentioned' => ['type' => 'boolean'],
                            'verification_status' => ['type' => 'string', 'enum' => ['verified', 'unverified']],
                            'context' => ['type' => 'string'],
                            'transcript_excerpt' => ['type' => 'string'],
                        ],
                        'required' => ['reference', 'explicitly_mentioned', 'verification_status', 'context', 'transcript_excerpt'],
                    ],
                ],
                'themes' => $stringArray,
                'applications' => $stringArray,
                'discussion_questions' => $stringArray,
            ],
            'required' => [
                'title', 'main_idea', 'summary', 'outline', 'key_points',
                'scripture_references', 'themes', 'applications', 'discussion_questions',
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $notes
     * @return array<string, mixed>
     */
    public function validate(array $notes): array
    {
        return Validator::make($notes, [
            'title' => ['required', 'string', 'max:180'],
            'main_idea' => ['required', 'string', 'max:2000'],
            'summary' => ['required', 'string', 'max:10000'],
            'outline' => ['required', 'array', 'list', 'max:30'],
            'outline.*.heading' => ['required', 'string', 'max:300'],
            'outline.*.details' => ['required', 'string', 'max:4000'],
            'key_points' => ['required', 'array', 'list', 'max:30'],
            'key_points.*' => ['required', 'string', 'max:2000'],
            'scripture_references' => ['present', 'array', 'list', 'max:100'],
            'scripture_references.*.reference' => ['required', 'string', 'max:200'],
            'scripture_references.*.explicitly_mentioned' => ['required', 'boolean'],
            'scripture_references.*.verification_status' => ['required', Rule::in(['verified', 'unverified'])],
            'scripture_references.*.context' => ['present', 'string', 'max:2000'],
            'scripture_references.*.transcript_excerpt' => ['present', 'string', 'max:1000'],
            'themes' => ['required', 'array', 'list', 'max:30'],
            'themes.*' => ['required', 'string', 'max:300'],
            'applications' => ['required', 'array', 'list', 'max:30'],
            'applications.*' => ['required', 'string', 'max:2000'],
            'discussion_questions' => ['required', 'array', 'list', 'max:30'],
            'discussion_questions.*' => ['required', 'string', 'max:2000'],
        ])->validate();
    }
}
