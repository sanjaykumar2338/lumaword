<?php

return [
    'daily_generation_per_user' => (int) env('DEVOTIONAL_DAILY_GENERATION_LIMIT', 3),
    'maximum_output_tokens' => (int) env('DEVOTIONAL_MAX_OUTPUT_TOKENS', 1600),
    'maximum_sermon_context_characters' => (int) env('DEVOTIONAL_MAX_SERMON_CONTEXT_CHARACTERS', 10000),
    'prompt_version' => env('DEVOTIONAL_PROMPT_VERSION', 'devotional-v1'),
];
