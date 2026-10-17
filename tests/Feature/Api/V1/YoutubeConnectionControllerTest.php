<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use App\Models\YoutubeConnection;
use App\Services\YoutubeApiClient;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YoutubeConnectionControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_user_cannot_read_connection_status(): void
    {
        $this->getJson('/api/v1/youtube/connection')->assertUnauthorized();
    }

    public function test_connection_status_exposes_channel_but_not_tokens(): void
    {
        config(['services.youtube.client_id' => 'client-id', 'services.youtube.client_secret' => 'client-secret']);
        $user = User::factory()->create();
        YoutubeConnection::factory()->for($user)->create(['channel_title' => 'Grace Church']);

        $response = $this->actingAs($user)->getJson('/api/v1/youtube/connection');

        $response
            ->assertOk()
            ->assertJsonPath('configured', true)
            ->assertJsonPath('connected', true)
            ->assertJsonPath('channel.title', 'Grace Church')
            ->assertJsonMissingPath('access_token')
            ->assertJsonMissingPath('refresh_token');
    }

    public function test_connection_start_returns_google_authorization_url_and_stores_state(): void
    {
        config([
            'services.youtube.client_id' => 'client-id',
            'services.youtube.client_secret' => 'client-secret',
            'services.youtube.redirect_uri' => 'https://luma.test/api/v1/youtube/callback',
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/v1/youtube/connection');

        $response->assertOk()->assertSessionHas('youtube_oauth_state');
        $authorizationUrl = $response->json('authorization_url');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $authorizationUrl);
        $this->assertStringContainsString(rawurlencode(YoutubeApiClient::SCOPE), $authorizationUrl);
    }

    public function test_callback_stores_encrypted_tokens_for_the_authorized_channel(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'plain-access-token',
                'refresh_token' => 'plain-refresh-token',
                'expires_in' => 3600,
                'scope' => YoutubeApiClient::SCOPE,
            ]),
            'https://www.googleapis.com/youtube/v3/channels*' => Http::response([
                'items' => [['id' => 'UC-owned-channel', 'snippet' => ['title' => 'Owned Church Channel']]],
            ]),
        ]);
        config([
            'services.youtube.client_id' => 'client-id',
            'services.youtube.client_secret' => 'client-secret',
            'services.youtube.redirect_uri' => 'https://luma.test/api/v1/youtube/callback',
        ]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['youtube_oauth_state' => 'expected-state'])
            ->get('/api/v1/youtube/callback?state=expected-state&code=authorization-code');

        $response->assertRedirect('/sermons/new?youtube=connected');
        $connection = YoutubeConnection::query()->sole();
        $this->assertSame('UC-owned-channel', $connection->channel_id);
        $this->assertSame('plain-access-token', $connection->access_token);
        $this->assertNotSame('plain-access-token', DB::table('youtube_connections')->value('access_token'));
        Http::assertSentCount(2);
    }

    public function test_callback_rejects_invalid_state_without_contacting_google(): void
    {
        Http::preventStrayRequests();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['youtube_oauth_state' => 'expected-state'])
            ->get('/api/v1/youtube/callback?state=wrong-state&code=authorization-code');

        $response->assertRedirect('/sermons/new?youtube=connection_failed');
        $this->assertDatabaseCount('youtube_connections', 0);
        Http::assertNothingSent();
    }

    public function test_disconnect_removes_only_the_authenticated_users_connection(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        YoutubeConnection::factory()->for($user)->create();
        $otherConnection = YoutubeConnection::factory()->for($otherUser)->create();

        $response = $this->actingAs($user)->deleteJson('/api/v1/youtube/connection');

        $response->assertOk()->assertJsonPath('message', 'YouTube channel disconnected.');
        $this->assertDatabaseMissing('youtube_connections', ['user_id' => $user->id]);
        $this->assertModelExists($otherConnection);
    }
}
