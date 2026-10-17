<?php

namespace Tests\Feature\Jobs;

use App\Jobs\FetchYoutubeTranscript;
use App\Jobs\ProcessSermon;
use App\Models\Sermon;
use App\Models\User;
use App\Models\YoutubeConnection;
use App\SermonProcessingStatus;
use App\SermonSourceType;
use App\Services\SermonProcessingDispatcher;
use App\Services\YoutubeApiClient;
use App\Services\YoutubeCaptionParser;
use App\TranscriptSource;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class FetchYoutubeTranscriptTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owned_video_caption_is_stored_and_notes_processing_is_queued(): void
    {
        Http::preventStrayRequests();
        Queue::fake([ProcessSermon::class]);
        $sermon = $this->youtubeSermon();
        $this->fakeYoutubeResponses('UC-owned-channel');

        (new FetchYoutubeTranscript($sermon->id))->handle(
            app(YoutubeApiClient::class),
            app(YoutubeCaptionParser::class),
            app(SermonProcessingDispatcher::class),
        );

        $sermon->refresh();
        $this->assertSame(SermonProcessingStatus::Queued, $sermon->processing_status);
        $this->assertSame(TranscriptSource::Provider, $sermon->transcript_source);
        $this->assertSame('caption-id', $sermon->provider_caption_id);
        $this->assertStringContainsString('God is faithful', $sermon->transcript);
        Queue::assertPushed(ProcessSermon::class, fn (ProcessSermon $job): bool => $job->sermonId === $sermon->id);
        Http::assertSentCount(3);
    }

    public function test_video_from_another_channel_requires_a_user_supplied_transcript(): void
    {
        Http::preventStrayRequests();
        Queue::fake([ProcessSermon::class]);
        $sermon = $this->youtubeSermon();
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [['id' => 'dQw4w9WgXcQ', 'snippet' => ['channelId' => 'UC-someone-else']]],
            ]),
        ]);

        (new FetchYoutubeTranscript($sermon->id))->handle(
            app(YoutubeApiClient::class),
            app(YoutubeCaptionParser::class),
            app(SermonProcessingDispatcher::class),
        );

        $sermon->refresh();
        $this->assertSame(SermonProcessingStatus::AwaitingTranscript, $sermon->processing_status);
        $this->assertNull($sermon->transcript);
        $this->assertStringContainsString('not owned by the connected YouTube channel', $sermon->processing_error);
        Queue::assertNotPushed(ProcessSermon::class);
        Http::assertSentCount(1);
    }

    public function test_video_without_downloadable_captions_requires_a_fallback(): void
    {
        Http::preventStrayRequests();
        Queue::fake([ProcessSermon::class]);
        $sermon = $this->youtubeSermon();
        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [['id' => 'dQw4w9WgXcQ', 'snippet' => ['channelId' => 'UC-owned-channel']]],
            ]),
            'https://www.googleapis.com/youtube/v3/captions*' => Http::response(['items' => []]),
        ]);

        (new FetchYoutubeTranscript($sermon->id))->handle(
            app(YoutubeApiClient::class),
            app(YoutubeCaptionParser::class),
            app(SermonProcessingDispatcher::class),
        );

        $sermon->refresh();
        $this->assertSame(SermonProcessingStatus::AwaitingTranscript, $sermon->processing_status);
        $this->assertStringContainsString('No downloadable caption track', $sermon->processing_error);
        Queue::assertNotPushed(ProcessSermon::class);
    }

    public function test_terminal_provider_failure_records_a_safe_error_without_provider_details(): void
    {
        $sermon = $this->youtubeSermon();

        (new FetchYoutubeTranscript($sermon->id))->failed(new RuntimeException('private provider response'));

        $sermon->refresh();
        $this->assertSame(SermonProcessingStatus::Failed, $sermon->processing_status);
        $this->assertStringNotContainsString('private provider response', $sermon->processing_error);
    }

    private function youtubeSermon(): Sermon
    {
        config(['services.youtube.client_id' => 'client-id', 'services.youtube.client_secret' => 'client-secret']);
        $user = User::factory()->create();
        YoutubeConnection::factory()->for($user)->create([
            'channel_id' => 'UC-owned-channel',
            'access_token' => 'access-token',
            'token_expires_at' => now()->addHour(),
        ]);

        return Sermon::factory()->for($user)->create([
            'source_type' => SermonSourceType::Url,
            'source_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'source_provider' => 'youtube',
            'provider_video_id' => 'dQw4w9WgXcQ',
            'processing_status' => SermonProcessingStatus::RetrievingTranscript,
            'transcript' => null,
            'transcript_source' => TranscriptSource::None,
            'processing_started_at' => now(),
        ]);
    }

    private function fakeYoutubeResponses(string $channelId): void
    {
        $caption = str_repeat('God is faithful in every season. ', 10);

        Http::fake([
            'https://www.googleapis.com/youtube/v3/videos*' => Http::response([
                'items' => [['id' => 'dQw4w9WgXcQ', 'snippet' => ['channelId' => $channelId]]],
            ]),
            'https://www.googleapis.com/youtube/v3/captions/caption-id*' => Http::response("WEBVTT\n\n00:00:00.000 --> 00:01:00.000\n{$caption}"),
            'https://www.googleapis.com/youtube/v3/captions*' => Http::response([
                'items' => [[
                    'id' => 'caption-id',
                    'snippet' => ['language' => 'en', 'status' => 'serving', 'isDraft' => false, 'trackKind' => 'standard'],
                ]],
            ]),
        ]);
    }
}
