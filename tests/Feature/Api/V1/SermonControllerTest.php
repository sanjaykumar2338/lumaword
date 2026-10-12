<?php

namespace Tests\Feature\Api\V1;

use App\Jobs\ProcessSermon;
use App\Models\Sermon;
use App\Models\SermonNote;
use App\Models\User;
use App\SermonProcessingStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SermonControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_when_user_is_unauthenticated(): void
    {
        $this->getJson('/api/v1/sermons')->assertUnauthorized();
    }

    public function test_valid_transcript_creates_sermon_queues_processing_and_returns_201(): void
    {
        Queue::fake([ProcessSermon::class]);
        $user = User::factory()->create();
        $transcript = str_repeat('Jesus teaches faithful prayer and trust. ', 10);

        $response = $this->actingAs($user)->postJson('/api/v1/sermons', [
            'source_type' => 'transcript',
            'title' => 'A Life of Prayer',
            'transcript' => $transcript,
        ]);

        $response->assertCreated()->assertJsonPath('data.processing_status', 'queued');
        $sermon = Sermon::query()->sole();
        $this->assertSame($user->id, $sermon->user_id);
        $this->assertSame(trim($transcript), $sermon->transcript);
        Queue::assertPushed(ProcessSermon::class, fn (ProcessSermon $job): bool => $job->sermonId === $sermon->id);
    }

    public function test_private_network_url_returns_422_and_creates_nothing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/sermons', [
            'source_type' => 'url',
            'source_url' => 'http://127.0.0.1/private-sermon',
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('source_url');
        $this->assertDatabaseCount('sermons', 0);
    }

    public function test_unsupported_upload_type_returns_422_and_stores_nothing(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/v1/sermons', [
            'source_type' => 'upload',
            'media' => UploadedFile::fake()->create('sermon.pdf', 10, 'application/pdf'),
        ], ['Accept' => 'application/json']);

        $response->assertUnprocessable()->assertJsonValidationErrors('media');
        $this->assertDatabaseCount('sermons', 0);
    }

    public function test_authorized_media_upload_is_stored_privately_and_queued(): void
    {
        Queue::fake([ProcessSermon::class]);
        Storage::fake('local');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/api/v1/sermons', [
            'source_type' => 'upload',
            'title' => 'Uploaded sermon',
            'media' => UploadedFile::fake()->create('sermon.mp3', 512, 'audio/mpeg'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonPath('data.processing_status', 'queued');
        $sermon = Sermon::query()->sole();
        $this->assertNotNull($sermon->media_path);
        Storage::disk('local')->assertExists($sermon->media_path);
        Queue::assertPushed(ProcessSermon::class, fn (ProcessSermon $job): bool => $job->sermonId === $sermon->id);
    }

    public function test_oversized_media_upload_returns_422_and_stores_nothing(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $maximumKilobytes = (int) config('sermons.limits.maximum_upload_kilobytes');

        $response = $this->actingAs($user)->post('/api/v1/sermons', [
            'source_type' => 'upload',
            'media' => UploadedFile::fake()->create('sermon.mp3', $maximumKilobytes + 1, 'audio/mpeg'),
        ], ['Accept' => 'application/json']);

        $response->assertUnprocessable()->assertJsonValidationErrors('media');
        $this->assertDatabaseCount('sermons', 0);
    }

    public function test_oversized_transcript_returns_422_and_creates_nothing(): void
    {
        $user = User::factory()->create();
        $maximumCharacters = (int) config('sermons.limits.maximum_transcript_characters');

        $response = $this->actingAs($user)->postJson('/api/v1/sermons', [
            'source_type' => 'transcript',
            'transcript' => str_repeat('x', $maximumCharacters + 1),
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('transcript');
        $this->assertDatabaseCount('sermons', 0);
    }

    public function test_user_cannot_view_another_users_sermon(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $sermon = Sermon::factory()->for($owner)->create();

        $this->actingAs($otherUser)->getJson("/api/v1/sermons/{$sermon->id}")->assertForbidden();
    }

    public function test_library_filters_only_the_authenticated_users_sermons(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sermon::factory()->for($user)->create([
            'title' => 'Grace in the Wilderness',
            'processing_status' => SermonProcessingStatus::Completed,
        ]);
        Sermon::factory()->for($user)->create(['title' => 'Unrelated Message']);
        Sermon::factory()->for($otherUser)->create([
            'title' => 'Grace in Another Account',
            'processing_status' => SermonProcessingStatus::Completed,
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/sermons?search=Grace&status=completed&per_page=10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Grace in the Wilderness')
            ->assertJsonMissingPath('data.0.transcript');
    }

    public function test_owner_can_update_title_and_read_status_and_notes(): void
    {
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->completed()->create();
        $notes = SermonNote::factory()->for($sermon)->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/sermons/{$sermon->id}", ['title' => 'Updated sermon title'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated sermon title');

        $this->actingAs($user)
            ->getJson("/api/v1/sermons/{$sermon->id}/status")
            ->assertOk()
            ->assertJsonPath('data.processing_status', 'completed');

        $this->actingAs($user)
            ->getJson("/api/v1/sermons/{$sermon->id}/notes")
            ->assertOk()
            ->assertJsonPath('data.id', $notes->id);
    }

    public function test_transcript_submission_replaces_notes_and_queues_processing(): void
    {
        Queue::fake([ProcessSermon::class]);
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->completed()->create();
        SermonNote::factory()->for($sermon)->create();
        $replacement = str_repeat('A replacement transcript grounded in the actual sermon. ', 8);

        $response = $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/transcript", [
            'transcript' => $replacement,
            'process' => true,
        ]);

        $response->assertOk()->assertJsonPath('data.processing_status', 'queued');
        $this->assertSame(trim($replacement), $sermon->refresh()->transcript);
        $this->assertDatabaseCount('sermon_notes', 0);
        Queue::assertPushed(ProcessSermon::class);
    }

    public function test_owner_can_delete_sermon_and_notes(): void
    {
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->create();
        SermonNote::factory()->for($sermon)->create();

        $this->actingAs($user)->deleteJson("/api/v1/sermons/{$sermon->id}")->assertNoContent();

        $this->assertDatabaseCount('sermons', 0);
        $this->assertDatabaseCount('sermon_notes', 0);
    }

    public function test_sixth_processing_request_returns_429(): void
    {
        Queue::fake([ProcessSermon::class]);
        RateLimiter::clear('');
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->create();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/process")->assertOk();
        }

        $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/process")->assertTooManyRequests();
    }
}
