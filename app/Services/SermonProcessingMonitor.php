<?php

namespace App\Services;

use App\Models\Sermon;
use App\SermonProcessingStatus;

class SermonProcessingMonitor
{
    public function failIfStale(Sermon $sermon): bool
    {
        $staleBefore = now()->subSeconds((int) config('sermons.processing.stale_after_seconds'));
        $updated = Sermon::query()
            ->whereKey($sermon->getKey())
            ->whereIn('processing_status', [
                SermonProcessingStatus::Submitted->value,
                SermonProcessingStatus::Transcribing->value,
                SermonProcessingStatus::Queued->value,
                SermonProcessingStatus::Analyzing->value,
            ])
            ->whereNotNull('processing_started_at')
            ->where('processing_started_at', '<=', $staleBefore)
            ->update([
                'processing_status' => SermonProcessingStatus::Failed,
                'processing_error' => 'Processing took longer than expected. Please retry. If it continues, contact support.',
            ]);

        if ($updated === 1) {
            $sermon->refresh();
        }

        return $updated === 1;
    }
}
