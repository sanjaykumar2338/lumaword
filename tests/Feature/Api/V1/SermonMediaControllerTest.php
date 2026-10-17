<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\ProcessSermon;
use App\Models\Sermon;
use App\Models\User;
use App\SermonProcessingStatus;
use App\SermonSourceType;
use App\TranscriptSource;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SermonMediaControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authorized_media_upload_preserves_the_original_link_and_queues_processing(): void
    {
        Queue::fake([ProcessSermon::class]);
        Storage::fake('local');
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->create([
            'source_type' => SermonSourceType::Url,
            'source_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'source_provider' => 'youtube',
            'processing_status' => SermonProcessingStatus::AwaitingTranscript,
            'transcript' => null,
            'transcript_source' => TranscriptSource::None,
        ]);

        $response = $this->actingAs($user)->post("/api/v1/sermons/{$sermon->id}/media", [
            'media' => UploadedFile::fake()->create('owned-sermon.mp3', 512, 'audio/mpeg'),
        ], ['Accept' => 'application/json']);

        $response
            ->assertOk()
            ->assertJsonPath('data.processing_status', 'queued')
            ->assertJsonPath('data.source_url', 'https://youtu.be/dQw4w9WgXcQ');
        $sermon->refresh();
        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $sermon->source_url);
        $this->assertNotNull($sermon->media_path);
        Storage::disk('local')->assertExists($sermon->media_path);
        Queue::assertPushed(ProcessSermon::class, fn (ProcessSermon $job): bool => $job->sermonId === $sermon->id);
    }

    public function test_user_cannot_upload_media_to_another_users_sermon(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $sermon = Sermon::factory()->for($owner)->create();

        $response = $this->actingAs($otherUser)->post("/api/v1/sermons/{$sermon->id}/media", [
            'media' => UploadedFile::fake()->create('sermon.mp3', 10, 'audio/mpeg'),
        ], ['Accept' => 'application/json']);

        $response->assertForbidden();
        Storage::disk('local')->assertDirectoryEmpty('sermons');
    }
}
