<?php

namespace Tests\Feature\Api\V1;

use App\Models\SermonCompanion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SermonCompanionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_authenticated_user_can_create_update_and_reload_a_companion(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->postJson('/api/v1/sermon-companions', ['title' => 'Faithful prayer', 'speaker' => 'Pastor A', 'personal_notes' => 'Prayer is a daily practice.', 'scripture_references' => ['John 3:16']]);
        $response->assertCreated()->assertJsonPath('data.status', 'awaiting_review');
        $companion = SermonCompanion::query()->sole();
        $this->actingAs($user)->patchJson("/api/v1/sermon-companions/{$companion->id}", ['confirmed_notes' => 'Prayer is a daily practice.'])->assertOk()->assertJsonPath('data.status', 'ready');
        $this->actingAs($user)->getJson("/api/v1/sermon-companions/{$companion->id}")->assertOk()->assertJsonPath('data.confirmed_notes', 'Prayer is a daily practice.');
    }

    public function test_journal_autosave_is_private_and_idempotent(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $companion = $user->sermonCompanions()->create(['title' => 'Private notes', 'status' => 'ready']);
        $payload = ['entry_type' => 'journal', 'client_key' => 'draft-1', 'content' => 'First answer'];
        $this->actingAs($user)->postJson("/api/v1/sermon-companions/{$companion->id}/journal", $payload)->assertCreated();
        $this->actingAs($user)->postJson("/api/v1/sermon-companions/{$companion->id}/journal", [...$payload, 'content' => 'Updated answer'])->assertOk();
        $this->assertDatabaseCount('sermon_companion_journal_entries', 1);
        $this->assertDatabaseHas('sermon_companion_journal_entries', ['content' => 'Updated answer']);
        $this->actingAs($otherUser)->getJson("/api/v1/sermon-companions/{$companion->id}/journal")->assertForbidden();
    }

    public function test_note_upload_is_validated_and_stored_on_private_disk(): void
    {
        Queue::fake();
        Storage::fake('local');
        $user = User::factory()->create();
        $companion = $user->sermonCompanions()->create(['title' => 'Handwritten notes', 'status' => 'draft']);
        $this->actingAs($user)->post("/api/v1/sermon-companions/{$companion->id}/attachments", ['kind' => 'image', 'file' => UploadedFile::fake()->create('notes.png', 10, 'image/png')], ['Accept' => 'application/json'])->assertOk();
        $attachment = $companion->attachments()->sole();
        $this->assertDatabaseHas('sermon_companion_attachments', ['sermon_companion_id' => $companion->id, 'kind' => 'image', 'status' => 'queued']);
        Storage::disk('local')->assertExists($attachment->path);
    }
}
