<?php

return [
    'daily_generation_per_user' => (int) env('BIBLE_STUDY_DAILY_GENERATION_LIMIT', 5),
    'maximum_output_tokens' => (int) env('BIBLE_STUDY_MAX_OUTPUT_TOKENS', 7000),
    'lock_seconds' => (int) env('BIBLE_STUDY_LOCK_SECONDS', 150),
    'prompt_version' => env('BIBLE_STUDY_PROMPT_VERSION', 'bible-study-v1'),
];
