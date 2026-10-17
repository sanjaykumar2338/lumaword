<?php

namespace App\Jobs;

use App\Exceptions\YoutubeAuthorizationException;
use App\Exceptions\YoutubeTranscriptUnavailableException;
use App\Models\Sermon;
use App\SermonProcessingStatus;
use App\Services\SermonProcessingDispatcher;
use App\Services\YoutubeApiClient;
use App\Services\YoutubeCaptionParser;
use App\TranscriptSource;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class FetchYoutubeTranscript implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public int $timeout = 60;

    public int $uniqueFor = 600;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly int $sermonId) {}

    /** @return array<int, object> */
    public function middleware(): array
    {
        return [(new RateLimited('youtube-transcripts'))->releaseAfter(60)];
    }

    public function uniqueId(): string
    {
        return (string) $this->sermonId;
    }

    public function handle(
        YoutubeApiClient $youtube,
        YoutubeCaptionParser $parser,
        SermonProcessingDispatcher $dispatcher,
    ): void {
        $sermon = Sermon::query()->with(['user.youtubeConnection'])->findOrFail($this->sermonId);

        if (filled($sermon->transcript) || $sermon->processing_status === SermonProcessingStatus::Completed) {
            return;
        }

        $connection = $sermon->user->youtubeConnection;

        if ($connection === null) {
            $this->awaitTranscript($sermon, 'Connect the YouTube channel that owns this video, or paste a transcript or upload authorized media.');

            return;
        }

        try {
            $video = $youtube->video($connection, (string) $sermon->provider_video_id);

            if ($video['channel_id'] !== $connection->channel_id) {
                throw new YoutubeTranscriptUnavailableException('This video is not owned by the connected YouTube channel. Paste a transcript or upload media you are authorized to use.');
            }

            $caption = filled($sermon->provider_caption_id)
                ? [
                    'id' => $sermon->provider_caption_id,
                    'snippet' => ['language' => $sermon->transcript_language],
                ]
                : $this->selectCaption($youtube->captions($connection, $video['id']));

            if (blank($sermon->provider_caption_id)) {
                $sermon->update([
                    'provider_caption_id' => $caption['id'],
                    'transcript_language' => Arr::get($caption, 'snippet.language'),
                ]);
            }

            $transcript = $parser->parseVtt($youtube->downloadCaption($connection, (string) $caption['id']));
            $this->validateTranscript($transcript);

            $sermon->update([
                'transcript' => $transcript,
                'transcript_source' => TranscriptSource::Provider,
                'transcript_language' => Arr::get($caption, 'snippet.language'),
                'provider_caption_id' => $caption['id'],
                'provider_retrieved_at' => now(),
                'processing_status' => SermonProcessingStatus::TranscriptReady,
                'processing_error' => null,
            ]);

            $dispatcher->dispatch($sermon, $sermon->user);
        } catch (YoutubeAuthorizationException $exception) {
            $this->awaitTranscript($sermon, 'YouTube access needs to be reconnected before Luma can retrieve this caption. You can still paste a transcript or upload authorized media.');
        } catch (YoutubeTranscriptUnavailableException $exception) {
            $this->awaitTranscript($sermon, $exception->getMessage());
        } catch (ValidationException $exception) {
            $sermon->update([
                'processing_status' => SermonProcessingStatus::TranscriptReady,
                'processing_error' => $exception->errors()['sermon'][0] ?? 'The transcript is ready, but notes processing could not be queued.',
            ]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        $sermon = Sermon::query()->find($this->sermonId);

        if ($sermon === null || filled($sermon->transcript)) {
            return;
        }

        $sermon->update([
            'processing_status' => SermonProcessingStatus::Failed,
            'processing_error' => 'Luma could not reach YouTube to retrieve the authorized transcript. Try again, paste a transcript, or upload authorized media.',
        ]);

        Log::error('Authorized YouTube transcript retrieval failed.', [
            'sermon_id' => $this->sermonId,
            'exception_class' => $exception ? $exception::class : null,
        ]);
    }

    /** @param array<int, array<string, mixed>> $captions */
    private function selectCaption(array $captions): array
    {
        $available = array_values(array_filter($captions, fn (array $caption): bool => Arr::get($caption, 'snippet.status') === 'serving'
            && Arr::get($caption, 'snippet.isDraft', false) !== true
            && is_string($caption['id'] ?? null)
        ));

        usort($available, fn (array $left, array $right): int => ($this->captionPriority($left) <=> $this->captionPriority($right))
        );

        if ($available === []) {
            throw new YoutubeTranscriptUnavailableException('No downloadable caption track is available for this video. Paste a transcript or upload authorized media to continue.');
        }

        return $available[0];
    }

    private function captionPriority(array $caption): int
    {
        return Arr::get($caption, 'snippet.trackKind') === 'standard' ? 0 : 1;
    }

    private function validateTranscript(string $transcript): void
    {
        $length = mb_strlen($transcript);

        if ($length < (int) config('sermons.limits.minimum_transcript_characters')) {
            throw new YoutubeTranscriptUnavailableException('The available YouTube caption is too short to create reliable notes. Paste a fuller transcript or upload authorized media.');
        }

        if ($length > (int) config('sermons.limits.maximum_transcript_characters')) {
            throw new YoutubeTranscriptUnavailableException('The available YouTube caption is longer than Luma can process safely. Paste a shorter authorized transcript.');
        }
    }

    private function awaitTranscript(Sermon $sermon, string $message): void
    {
        $sermon->update([
            'processing_status' => SermonProcessingStatus::AwaitingTranscript,
            'processing_error' => $message,
        ]);
    }
}
