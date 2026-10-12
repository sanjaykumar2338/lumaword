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

export type SermonProcessingStatus = 'submitted' | 'awaiting_transcript' | 'transcribing' | 'transcript_ready' | 'queued' | 'analyzing' | 'completed' | 'failed';

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
