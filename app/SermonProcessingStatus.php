<?php

namespace App;

enum SermonProcessingStatus: string
{
    case Submitted = 'submitted';
    case AwaitingTranscript = 'awaiting_transcript';
    case Transcribing = 'transcribing';
    case TranscriptReady = 'transcript_ready';
    case Queued = 'queued';
    case Analyzing = 'analyzing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function isActive(): bool
    {
        return in_array($this, [self::Transcribing, self::Queued, self::Analyzing], true);
    }
}
