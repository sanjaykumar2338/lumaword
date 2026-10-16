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
        'stale_after_seconds' => (int) env('SERMON_PROCESSING_STALE_AFTER_SECONDS', 1800),
        'prompt_version' => env('SERMON_PROMPT_VERSION', 'sermon-notes-v1'),
        'max_output_tokens' => (int) env('SERMON_MAX_OUTPUT_TOKENS', 6000),
    ],
    'qa' => [
        'maximum_question_characters' => (int) env('SERMON_QA_MAX_QUESTION_CHARACTERS', 2000),
        'daily_questions_per_user' => (int) env('SERMON_QA_DAILY_QUESTIONS', 20),
        'recent_message_count' => (int) env('SERMON_QA_RECENT_MESSAGES', 8),
        'maximum_output_tokens' => (int) env('SERMON_QA_MAX_OUTPUT_TOKENS', 1200),
        'maximum_context_characters' => (int) env('SERMON_QA_MAX_CONTEXT_CHARACTERS', 12000),
        'maximum_context_chunks' => (int) env('SERMON_QA_MAX_CONTEXT_CHUNKS', 6),
        'context_chunk_characters' => (int) env('SERMON_QA_CONTEXT_CHUNK_CHARACTERS', 1800),
        'source_excerpt_characters' => (int) env('SERMON_QA_SOURCE_EXCERPT_CHARACTERS', 500),
        'messages_per_page' => (int) env('SERMON_QA_MESSAGES_PER_PAGE', 20),
        'timeout_seconds' => (int) env('SERMON_QA_TIMEOUT', 45),
        'lock_seconds' => (int) env('SERMON_QA_LOCK_SECONDS', 60),
    ],
    'upload_extensions' => ['flac', 'mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'ogg', 'wav', 'webm'],
];
