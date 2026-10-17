import type { SermonProcessingStatus } from '../types';

const labels: Record<SermonProcessingStatus, string> = {
    submitted: 'Submitted',
    awaiting_transcript: 'Needs transcript',
    retrieving_transcript: 'Checking captions',
    transcribing: 'Transcribing',
    transcript_ready: 'Transcript ready',
    queued: 'Queued',
    analyzing: 'Creating notes',
    completed: 'Completed',
    failed: 'Needs attention',
};

export function SermonStatusBadge({ status }: { status: SermonProcessingStatus }) {
    const active = ['submitted', 'retrieving_transcript', 'transcribing', 'queued', 'analyzing'].includes(status);
    const color = status === 'completed'
        ? 'bg-lime-200 text-moss'
        : status === 'failed'
          ? 'bg-red-100 text-red-800'
          : status === 'awaiting_transcript'
            ? 'bg-amber-100 text-amber-900'
            : 'bg-stone-200 text-stone-700';

    return (
        <span className={`inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ${color}`}>
            {active && <span className="size-1.5 animate-pulse rounded-full bg-current" />}
            {labels[status]}
        </span>
    );
}
