<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator as LaravelValidator;

class BibleStudyContentSchema
{
    /** @return array<string, mixed> */
    public function schema(): array
    {
        $stringArray = [
            'type' => 'array',
            'items' => ['type' => 'string'],
        ];

        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'title', 'overview', 'passage_context', 'book_introduction', 'historical_context',
                'word_studies', 'book_outline', 'literary_context', 'themes', 'cross_references',
                'study_questions', 'application',
            ],
            'properties' => [
                'title' => ['type' => 'string'],
                'overview' => ['type' => 'string'],
                'passage_context' => ['type' => 'string'],
                'book_introduction' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['authorship', 'audience', 'purpose', 'major_themes', 'biblical_narrative', 'scholarly_uncertainties'],
                    'properties' => [
                        'authorship' => ['type' => 'string'],
                        'audience' => ['type' => 'string'],
                        'purpose' => ['type' => 'string'],
                        'major_themes' => $stringArray,
                        'biblical_narrative' => ['type' => 'string'],
                        'scholarly_uncertainties' => $stringArray,
                    ],
                ],
                'historical_context' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['detail', 'certainty'],
                        'properties' => [
                            'detail' => ['type' => 'string'],
                            'certainty' => ['type' => 'string', 'enum' => ['established', 'likely', 'debated']],
                        ],
                    ],
                ],
                'word_studies' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => [
                            'term', 'language', 'transliteration', 'pronunciation', 'contextual_meaning',
                            'possible_translations', 'translation_significance', 'caution',
                        ],
                        'properties' => [
                            'term' => ['type' => 'string'],
                            'language' => ['type' => 'string', 'enum' => ['Hebrew', 'Aramaic', 'Greek']],
                            'transliteration' => ['type' => 'string'],
                            'pronunciation' => ['type' => 'string'],
                            'contextual_meaning' => ['type' => 'string'],
                            'possible_translations' => $stringArray,
                            'translation_significance' => ['type' => 'string'],
                            'caution' => ['type' => 'string'],
                        ],
                    ],
                ],
                'book_outline' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['heading', 'range', 'summary', 'contains_passage'],
                        'properties' => [
                            'heading' => ['type' => 'string'],
                            'range' => ['type' => 'string'],
                            'summary' => ['type' => 'string'],
                            'contains_passage' => ['type' => 'boolean'],
                        ],
                    ],
                ],
                'literary_context' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['genre', 'structure', 'observations'],
                    'properties' => [
                        'genre' => ['type' => 'string'],
                        'structure' => $stringArray,
                        'observations' => $stringArray,
                    ],
                ],
                'themes' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['theme', 'explanation'],
                        'properties' => [
                            'theme' => ['type' => 'string'],
                            'explanation' => ['type' => 'string'],
                        ],
                    ],
                ],
                'cross_references' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['reference', 'connection'],
                        'properties' => [
                            'reference' => ['type' => 'string'],
                            'connection' => ['type' => 'string'],
                        ],
                    ],
                ],
                'study_questions' => $stringArray,
                'application' => [
                    'type' => 'object',
                    'additionalProperties' => false,
                    'required' => ['practical_reflections', 'prayer_prompts', 'journaling_questions', 'group_discussion_topics'],
                    'properties' => [
                        'practical_reflections' => $stringArray,
                        'prayer_prompts' => $stringArray,
                        'journaling_questions' => $stringArray,
                        'group_discussion_topics' => $stringArray,
                    ],
                ],
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     */
    public function validate(array $content): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:180'],
            'overview' => ['required', 'string', 'max:8000'],
            'passage_context' => ['required', 'string', 'max:8000'],
            'book_introduction' => ['required', 'array'],
            'book_introduction.authorship' => ['required', 'string', 'max:4000'],
            'book_introduction.audience' => ['required', 'string', 'max:4000'],
            'book_introduction.purpose' => ['required', 'string', 'max:4000'],
            'book_introduction.major_themes' => ['required', 'array', 'list', 'max:20'],
            'book_introduction.major_themes.*' => ['required', 'string', 'max:500'],
            'book_introduction.biblical_narrative' => ['required', 'string', 'max:4000'],
            'book_introduction.scholarly_uncertainties' => ['present', 'array', 'list', 'max:20'],
            'book_introduction.scholarly_uncertainties.*' => ['required', 'string', 'max:2000'],
            'historical_context' => ['present', 'array', 'list', 'max:20'],
            'historical_context.*.detail' => ['required', 'string', 'max:3000'],
            'historical_context.*.certainty' => ['required', Rule::in(['established', 'likely', 'debated'])],
            'word_studies' => ['present', 'array', 'list', 'max:12'],
            'word_studies.*.term' => ['required', 'string', 'max:200'],
            'word_studies.*.language' => ['required', Rule::in(['Hebrew', 'Aramaic', 'Greek'])],
            'word_studies.*.transliteration' => ['required', 'string', 'max:200'],
            'word_studies.*.pronunciation' => ['required', 'string', 'max:200'],
            'word_studies.*.contextual_meaning' => ['required', 'string', 'max:3000'],
            'word_studies.*.possible_translations' => ['required', 'array', 'list', 'max:15'],
            'word_studies.*.possible_translations.*' => ['required', 'string', 'max:300'],
            'word_studies.*.translation_significance' => ['required', 'string', 'max:3000'],
            'word_studies.*.caution' => ['required', 'string', 'max:2000'],
            'book_outline' => ['required', 'array', 'list', 'min:1', 'max:30'],
            'book_outline.*.heading' => ['required', 'string', 'max:300'],
            'book_outline.*.range' => ['required', 'string', 'max:200'],
            'book_outline.*.summary' => ['required', 'string', 'max:2000'],
            'book_outline.*.contains_passage' => ['required', 'boolean'],
            'literary_context' => ['required', 'array'],
            'literary_context.genre' => ['required', 'string', 'max:500'],
            'literary_context.structure' => ['required', 'array', 'list', 'max:20'],
            'literary_context.structure.*' => ['required', 'string', 'max:2000'],
            'literary_context.observations' => ['required', 'array', 'list', 'max:20'],
            'literary_context.observations.*' => ['required', 'string', 'max:2000'],
            'themes' => ['required', 'array', 'list', 'min:1', 'max:20'],
            'themes.*.theme' => ['required', 'string', 'max:300'],
            'themes.*.explanation' => ['required', 'string', 'max:3000'],
            'cross_references' => ['present', 'array', 'list', 'max:20'],
            'cross_references.*.reference' => [
                'required',
                'string',
                'max:200',
                'regex:/^(?:[1-3] )?[A-Za-z]+(?: [A-Za-z]+)* \d+:\d+(?:-\d+)?$/',
            ],
            'cross_references.*.connection' => ['required', 'string', 'max:2000'],
            'study_questions' => ['required', 'array', 'list', 'min:1', 'max:20'],
            'study_questions.*' => ['required', 'string', 'max:2000'],
            'application' => ['required', 'array'],
            'application.practical_reflections' => ['present', 'array', 'list', 'max:20'],
            'application.practical_reflections.*' => ['required', 'string', 'max:2000'],
            'application.prayer_prompts' => ['present', 'array', 'list', 'max:20'],
            'application.prayer_prompts.*' => ['required', 'string', 'max:2000'],
            'application.journaling_questions' => ['present', 'array', 'list', 'max:20'],
            'application.journaling_questions.*' => ['required', 'string', 'max:2000'],
            'application.group_discussion_topics' => ['present', 'array', 'list', 'max:20'],
            'application.group_discussion_topics.*' => ['required', 'string', 'max:2000'],
        ];

        $validator = Validator::make($content, $rules);
        $validator->after(function (LaravelValidator $validator) use ($content): void {
            $containsPassage = collect($content['book_outline'] ?? [])
                ->contains(fn (mixed $section): bool => is_array($section)
                    && ($section['contains_passage'] ?? false) === true);

            if (! $containsPassage) {
                $validator->errors()->add('book_outline', 'The outline must identify where the selected passage fits.');
            }
        });

        return $validator->validate();
    }
}
