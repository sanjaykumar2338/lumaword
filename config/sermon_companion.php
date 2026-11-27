<?php

return [
    'upload' => ['max_kb' => (int) env('SERMON_COMPANION_MAX_UPLOAD_KB', 25600)],
    'ai' => ['max_output_tokens' => (int) env('SERMON_COMPANION_MAX_OUTPUT_TOKENS', 5000), 'prompt_version' => env('SERMON_COMPANION_PROMPT_VERSION', 'companion-v2')],
];
