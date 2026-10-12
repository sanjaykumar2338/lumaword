<?php

namespace App\Jobs;

use App\Contracts\MediaTranscriber;
use App\Contracts\SermonAnalyzer;
use App\Models\Sermon;
use App\SermonProcessingStatus;
use App\TranscriptSource;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class ProcessSermon implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout;

    public int $uniqueFor;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 300];

    public function __construct(public readonly int $sermonId)
    {
        $this->timeout = (int) config('sermons.processing.timeout_seconds');
        $this->uniqueFor = (int) config('sermons.processing.lock_seconds');
    }

    public function uniqueId(): string
    {
        return (string) $this->sermonId;
    }

    public function handle(SermonAnalyzer $analyzer, MediaTranscriber $transcriber): void
    {
        Cache::lock('sermon-processing:'.$this->sermonId, (int) config('sermons.processing.lock_seconds'))
            ->block(5, function () use ($analyzer, $transcriber): void {
                $sermon = Sermon::query()->findOrFail($this->sermonId);

                if ($sermon->processing_status === SermonProcessingStatus::Completed) {
                    return;
                }

                $sermon->increment('processing_attempts');

                if (blank($sermon->transcript)) {
                    $this->transcribe($sermon, $transcriber);
                    $sermon->refresh();
                }

                $maximumCharacters = (int) config('sermons.limits.maximum_transcript_characters');

                if (mb_strlen((string) $sermon->transcript) > $maximumCharacters) {
                    throw new RuntimeException('The transcript exceeds the configured processing limit.');
                }

                $sermon->update(['processing_status' => SermonProcessingStatus::Analyzing]);
                $result = $analyzer->analyze($sermon);
                $notes = $result['notes'];

                DB::transaction(function () use ($sermon, $notes, $result): void {
                    $sermon->notes()->updateOrCreate([], [
                        'summary' => $notes['summary'],
                        'main_idea' => $notes['main_idea'],
                        'outline' => $notes['outline'],
                        'key_points' => $notes['key_points'],
                        'scripture_references' => $notes['scripture_references'],
                        'themes' => $notes['themes'],
                        'applications' => $notes['applications'],
                        'discussion_questions' => $notes['discussion_questions'],
                        'ai_provider' => 'openai',
                        'ai_model' => config('services.openai.model'),
                        'ai_response_id' => $result['response_id'],
                        'prompt_version' => config('sermons.processing.prompt_version'),
                        'usage' => $result['usage'],
                    ]);

                    $sermon->update([
                        'title' => $sermon->title === 'Untitled sermon' ? $notes['title'] : $sermon->title,
                        'processing_status' => SermonProcessingStatus::Completed,
                        'processing_error' => null,
                        'processed_at' => now(),
                    ]);
                });
            });
    }

    public function failed(?Throwable $exception): void
    {
        $sermon = Sermon::query()->find($this->sermonId);

        if ($sermon === null) {
            return;
        }

        $sermon->update([
            'processing_status' => SermonProcessingStatus::Failed,
            'processing_error' => 'Processing could not be completed. You can safely retry.',
        ]);

        Log::error('Sermon processing failed.', [
            'sermon_id' => $this->sermonId,
            'exception_class' => $exception ? $exception::class : null,
        ]);
    }

    private function transcribe(Sermon $sermon, MediaTranscriber $transcriber): void
    {
        $sermon->update(['processing_status' => SermonProcessingStatus::Transcribing]);
        $transcription = $transcriber->transcribe($sermon);
        $duration = $transcription['duration_seconds'];

        if ($duration !== null && $duration > config('sermons.limits.maximum_duration_seconds')) {
            throw new RuntimeException('The media duration exceeds the configured processing limit.');
        }

        $mediaPath = $sermon->media_path;
        $sermon->update([
            'transcript' => $transcription['text'],
            'transcript_source' => TranscriptSource::Upload,
            'transcript_language' => $transcription['language'],
            'duration_seconds' => $duration,
            'processing_status' => SermonProcessingStatus::TranscriptReady,
            'media_path' => null,
        ]);

        if (is_string($mediaPath)) {
            Storage::disk('local')->delete($mediaPath);
        }
    }
}
