<?php

namespace App\Services;

use App\Exceptions\YoutubeAuthorizationException;
use App\Exceptions\YoutubeTranscriptUnavailableException;
use App\Models\YoutubeConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

class YoutubeApiClient
{
    public const SCOPE = 'https://www.googleapis.com/auth/youtube.force-ssl';

    public function configured(): bool
    {
        return filled(config('services.youtube.client_id')) && filled(config('services.youtube.client_secret'));
    }

    public function authorizationUrl(string $state): string
    {
        if (! $this->configured()) {
            throw new YoutubeAuthorizationException('YouTube connection is not configured.');
        }

        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query([
            'client_id' => config('services.youtube.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** @return array{access_token: string, refresh_token: ?string, expires_in: int, scope: string} */
    public function exchangeCode(string $code): array
    {
        $response = $this->tokenRequest([
            'code' => $code,
            'client_id' => config('services.youtube.client_id'),
            'client_secret' => config('services.youtube.client_secret'),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        return $this->tokenPayload($response);
    }

    /** @return array{id: string, title: string} */
    public function channel(string $accessToken): array
    {
        $response = $this->apiRequest($accessToken)->get('/youtube/v3/channels', [
            'part' => 'id,snippet',
            'mine' => 'true',
            'maxResults' => 1,
        ]);

        if ($response->unauthorized() || $response->forbidden()) {
            throw new YoutubeAuthorizationException('YouTube did not authorize access to this channel.');
        }

        $response->throw();
        $channel = $response->json('items.0');

        if (! is_array($channel) || ! is_string($channel['id'] ?? null)) {
            throw new YoutubeAuthorizationException('The connected Google account does not have a YouTube channel.');
        }

        return [
            'id' => $channel['id'],
            'title' => (string) Arr::get($channel, 'snippet.title', 'YouTube channel'),
        ];
    }

    /** @return array{id: string, channel_id: string} */
    public function video(YoutubeConnection $connection, string $videoId): array
    {
        $response = $this->authorizedGet($connection, '/youtube/v3/videos', [
            'part' => 'id,snippet',
            'id' => $videoId,
            'maxResults' => 1,
        ]);
        $video = $response->json('items.0');

        if (! is_array($video) || ! is_string(Arr::get($video, 'snippet.channelId'))) {
            throw new YoutubeTranscriptUnavailableException('This YouTube video could not be found.');
        }

        return ['id' => (string) $video['id'], 'channel_id' => (string) Arr::get($video, 'snippet.channelId')];
    }

    /** @return array<int, array<string, mixed>> */
    public function captions(YoutubeConnection $connection, string $videoId): array
    {
        $response = $this->authorizedGet($connection, '/youtube/v3/captions', [
            'part' => 'id,snippet',
            'videoId' => $videoId,
        ]);

        return array_values(array_filter($response->json('items', []), 'is_array'));
    }

    public function downloadCaption(YoutubeConnection $connection, string $captionId): string
    {
        return $this->authorizedGet($connection, '/youtube/v3/captions/'.rawurlencode($captionId), [
            'tfmt' => 'vtt',
        ])->body();
    }

    public function redirectUri(): string
    {
        return (string) (config('services.youtube.redirect_uri') ?: route('api.v1.youtube.callback'));
    }

    private function authorizedGet(YoutubeConnection $connection, string $path, array $query): Response
    {
        $response = $this->apiRequest($this->accessToken($connection))->get($path, $query);

        if ($response->unauthorized()) {
            $response = $this->apiRequest($this->accessToken($connection, true))->get($path, $query);
        }

        if ($response->unauthorized() || $response->forbidden()) {
            throw new YoutubeAuthorizationException('Reconnect YouTube to confirm access to this video.');
        }

        $response->throw();

        return $response;
    }

    private function accessToken(YoutubeConnection $connection, bool $forceRefresh = false): string
    {
        if (! $forceRefresh && $connection->token_expires_at->isAfter(now()->addMinute())) {
            return $connection->access_token;
        }

        if (blank($connection->refresh_token)) {
            throw new YoutubeAuthorizationException('Reconnect YouTube to continue.');
        }

        $payload = $this->tokenPayload($this->tokenRequest([
            'client_id' => config('services.youtube.client_id'),
            'client_secret' => config('services.youtube.client_secret'),
            'refresh_token' => $connection->refresh_token,
            'grant_type' => 'refresh_token',
        ]));

        $connection->update([
            'access_token' => $payload['access_token'],
            'refresh_token' => $payload['refresh_token'] ?: $connection->refresh_token,
            'token_expires_at' => now()->addSeconds($payload['expires_in']),
            'scopes' => preg_split('/\s+/', trim($payload['scope'])) ?: [self::SCOPE],
        ]);

        return $payload['access_token'];
    }

    private function apiRequest(string $accessToken): PendingRequest
    {
        return Http::baseUrl('https://www.googleapis.com')
            ->acceptJson()
            ->withToken($accessToken)
            ->connectTimeout((int) config('services.youtube.connect_timeout'))
            ->timeout((int) config('services.youtube.timeout'));
    }

    private function tokenRequest(array $fields): Response
    {
        $response = Http::asForm()
            ->acceptJson()
            ->connectTimeout((int) config('services.youtube.connect_timeout'))
            ->timeout((int) config('services.youtube.timeout'))
            ->post('https://oauth2.googleapis.com/token', $fields);

        if (! $response->successful()) {
            throw new YoutubeAuthorizationException('YouTube authorization could not be completed.');
        }

        return $response;
    }

    /** @return array{access_token: string, refresh_token: ?string, expires_in: int, scope: string} */
    private function tokenPayload(Response $response): array
    {
        $accessToken = $response->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new YoutubeAuthorizationException('YouTube did not return a usable access token.');
        }

        return [
            'access_token' => $accessToken,
            'refresh_token' => is_string($response->json('refresh_token')) ? $response->json('refresh_token') : null,
            'expires_in' => max(60, (int) $response->json('expires_in', 3600)),
            'scope' => (string) $response->json('scope', self::SCOPE),
        ];
    }
}
