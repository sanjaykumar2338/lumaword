<?php

namespace App\Services;

use App\Jobs\FetchYoutubeTranscript;
use App\Models\Sermon;
use App\Models\User;
use App\SermonProcessingStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class YoutubeTranscriptDispatcher
{
    public function dispatch(Sermon $sermon, User $user): bool
    {
        if ($sermon->source_provider !== 'youtube' || blank($sermon->provider_video_id)) {
            throw ValidationException::withMessages([
                'sermon' => 'This sermon does not contain a supported YouTube video link.',
            ]);
        }

        if (! $user->youtubeConnection()->exists()) {
            throw ValidationException::withMessages([
                'sermon' => 'Connect the YouTube channel that owns this video, or add an authorized transcript or media file.',
            ]);
        }

        $startedToday = $user->sermons()
            ->whereKeyNot($sermon->id)
            ->where('processing_started_at', '>=', now()->startOfDay())
            ->count();

        if ($startedToday >= config('sermons.limits.daily_processing_per_user')) {
            throw ValidationException::withMessages([
                'sermon' => 'Your configurable daily sermon-processing limit has been reached. Try again tomorrow.',
            ]);
        }

        return DB::transaction(function () use ($sermon): bool {
            $lockedSermon = Sermon::query()->lockForUpdate()->findOrFail($sermon->id);

            if ($lockedSermon->processing_status->isActive()) {
                return false;
            }

            $lockedSermon->update([
                'processing_status' => SermonProcessingStatus::RetrievingTranscript,
                'processing_error' => null,
                'processing_started_at' => now(),
                'processed_at' => null,
            ]);

            FetchYoutubeTranscript::dispatch($lockedSermon->id)->afterCommit();

            return true;
        });
    }
}
