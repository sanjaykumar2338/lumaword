<?php

namespace App\Services;

use App\Jobs\ProcessSermon;
use App\Models\Sermon;
use App\Models\User;
use App\SermonProcessingStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SermonProcessingDispatcher
{
    public function __construct(private readonly AiUsageService $usage) {}

    public function dispatch(Sermon $sermon, User $user): bool
    {
        $startedToday = $user->sermons()
            ->whereKeyNot($sermon->id)
            ->where('processing_started_at', '>=', now()->startOfDay())
            ->count();

        if ($startedToday >= config('sermons.limits.daily_processing_per_user')) {
            throw ValidationException::withMessages([
                'sermon' => 'Your configurable daily sermon-processing limit has been reached. Try again tomorrow.',
            ]);
        }

        return DB::transaction(function () use ($sermon, $user): bool {
            $lockedSermon = Sermon::query()->lockForUpdate()->findOrFail($sermon->id);

            if ($lockedSermon->processing_status->isActive()) {
                return false;
            }

            if (blank($lockedSermon->transcript) && blank($lockedSermon->media_path)) {
                throw ValidationException::withMessages([
                    'sermon' => 'Add a transcript or authorized media file before processing.',
                ]);
            }

            $fingerprint = hash('sha256', implode('|', [
                (string) $lockedSermon->transcript,
                (string) $lockedSermon->media_path,
                (string) config('sermons.processing.prompt_version'),
            ]));
            $reservation = $this->usage->reserve($user, 'sermon_processing', "sermon:{$lockedSermon->id}:{$fingerprint}");

            $lockedSermon->update([
                'processing_status' => SermonProcessingStatus::Queued,
                'processing_error' => null,
                'processing_started_at' => now(),
                'processed_at' => null,
            ]);

            ProcessSermon::dispatch($lockedSermon->id, $reservation->id)->afterCommit();

            return true;
        });
    }
}
