<?php

return [
    'limits' => [
        'minimum_transcript_characters' => (int) env('SERMON_MIN_TRANSCRIPT_CHARACTERS', 200),
        'maximum_transcript_characters' => (int) env('SERMON_MAX_TRANSCRIPT_CHARACTERS', 120000),
        'maximum_upload_kilobytes' => (int) env('SERMON_MAX_UPLOAD_KB', 25600),
        'maximum_duration_seconds' => (int) env('SERMON_MAX_DURATION_SECONDS', 7200),
        'daily_processing_per_user' => (int) env('SERMON_DAILY_PROCESSING_LIMIT', 10),
    ],
    'processing' => [
        'timeout_seconds' => (int) env('SERMON_JOB_TIMEOUT', 180),
        'lock_seconds' => (int) env('SERMON_PROCESSING_LOCK_SECONDS', 240),
        'prompt_version' => env('SERMON_PROMPT_VERSION', 'sermon-notes-v1'),
        'max_output_tokens' => (int) env('SERMON_MAX_OUTPUT_TOKENS', 6000),
    ],
    'upload_extensions' => ['flac', 'mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'ogg', 'wav', 'webm'],
];
