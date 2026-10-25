export type User = {
    id: number;
    name: string;
    email: string;
    created_at: string;
};

export type ResourceResponse<T> = {
    data: T;
    message?: string;
};

export type MessageResponse = {
    message: string;
};

export type SermonProcessingStatus = 'submitted' | 'awaiting_transcript' | 'retrieving_transcript' | 'transcribing' | 'transcript_ready' | 'queued' | 'analyzing' | 'completed' | 'failed';

export type YoutubeConnectionStatus = {
    configured: boolean;
    connected: boolean;
    channel: { id: string; title: string } | null;
};

export type SermonNote = {
    id: number;
    summary: string;
    main_idea: string;
    outline: Array<{ heading: string; details: string }>;
    key_points: string[];
    scripture_references: Array<{ reference: string; explicitly_mentioned: boolean; verification_status: 'verified' | 'unverified'; context: string; transcript_excerpt: string }>;
    themes: string[];
    applications: string[];
    discussion_questions: string[];
    ai: { provider: string; model: string; prompt_version: string };
};

export type Sermon = {
    id: number;
    title: string;
    source_type: 'url' | 'transcript' | 'upload';
    source_url: string | null;
    source_provider: string | null;
    provider_video_id: string | null;
    processing_status: SermonProcessingStatus;
    processing_error: string | null;
    transcript?: string | null;
    has_transcript: boolean;
    transcript_source: 'none' | 'pasted' | 'provider' | 'upload';
    transcript_language: string | null;
    duration_seconds: number | null;
    processing_started_at: string | null;
    processed_at: string | null;
    notes?: SermonNote | null;
    created_at: string;
    updated_at: string;
};

export type PaginatedResponse<T> = {
    data: T[];
    links: { first: string | null; last: string | null; prev: string | null; next: string | null };
    meta: { current_page: number; last_page: number; per_page: number; total: number };
};

export type SermonConversation = {
    id: number;
    sermon_id: number;
    title: string | null;
    message_count?: number;
    created_at: string;
    updated_at: string;
};

export type SermonMessage = {
    id: number;
    role: 'user' | 'assistant';
    content: string;
    sources: Array<{ section: string; excerpt: string }>;
    status: 'pending' | 'completed' | 'failed';
    client_request_id: string | null;
    created_at: string;
};

export type Devotional = {
    id: number | null;
    sermon_id?: number | null;
    source_type: 'daily' | 'passage' | 'sermon';
    title: string;
    scripture_key: string;
    scripture_reference: string;
    translation: string;
    scripture_text: string;
    reflection: string;
    application: string;
    prayer: string;
    is_ai_generated: boolean;
    generated_at: string | null;
    created_at?: string;
};

export type ScriptureOption = {
    key: string;
    reference: string;
    translation: string;
};

export type ReadingPlanDay = {
    id: number;
    day_number: number;
    title: string;
    scripture_key: string;
    scripture_reference: string;
    translation: string;
    scripture_text: string;
};

export type ReadingPlanEnrollmentSummary = {
    id: number;
    started_at: string;
    completed_at: string | null;
    completed_day_ids: number[];
};

export type ReadingPlan = {
    id: number;
    slug: string;
    title: string;
    description: string;
    duration_days: number;
    days: ReadingPlanDay[];
    enrollment: ReadingPlanEnrollmentSummary | null;
};

export type UserReadingPlan = {
    id: number;
    started_at: string;
    completed_at: string | null;
    completed_days: number;
    completed_day_ids: number[];
    plan: ReadingPlan;
};

export type DailyActivitySummary = {
    current_streak: number;
    longest_streak: number;
    total_active_days: number;
    timezone: string;
    last_seven_days: Array<{
        date: string;
        label: string;
        active: boolean;
        is_today: boolean;
    }>;
};

export type TimezoneOption = {
    value: string;
    label: string;
};

export type DevotionalReminderPreference = {
    enabled: boolean;
    reminder_time: string;
    timezone: string;
    timezone_configured: boolean;
    timezone_options: TimezoneOption[];
    push_supported: boolean;
    vapid_public_key: string | null;
    subscription_count: number;
};
