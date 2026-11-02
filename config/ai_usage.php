<?php

return [
    'enabled' => (bool) env('AI_FEATURES_ENABLED', true),
    'system_monthly_cost_limit_micros' => (int) env('AI_SYSTEM_MONTHLY_COST_LIMIT_MICROS', 100000000),
    'estimated_input_cost_per_million_micros' => (int) env('AI_INPUT_COST_PER_MILLION_MICROS', 150000),
    'estimated_output_cost_per_million_micros' => (int) env('AI_OUTPUT_COST_PER_MILLION_MICROS', 600000),
    'limits' => [
        'free' => ['sermon_processing' => 2, 'ask_luma' => 10, 'bible_study' => 2, 'devotional' => 2],
        'premium' => ['sermon_processing' => 30, 'ask_luma' => 300, 'bible_study' => 30, 'devotional' => 30],
    ],
    'feature_enabled' => [
        'sermon_processing' => (bool) env('AI_SERMON_PROCESSING_ENABLED', true),
        'ask_luma' => (bool) env('AI_ASK_LUMA_ENABLED', true),
        'bible_study' => (bool) env('AI_BIBLE_STUDY_ENABLED', true),
        'devotional' => (bool) env('AI_DEVOTIONAL_ENABLED', true),
    ],
];
