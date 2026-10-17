<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\YoutubeApiClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class YoutubeOAuthCallbackController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, YoutubeApiClient $youtube): RedirectResponse
    {
        $expectedState = $request->session()->pull('youtube_oauth_state');
        $state = $request->string('state')->toString();

        if ($request->filled('error') || ! is_string($expectedState) || $state === '' || ! hash_equals($expectedState, $state)) {
            return redirect('/sermons/new?youtube=connection_failed');
        }

        $code = $request->string('code')->toString();

        if ($code === '') {
            return redirect('/sermons/new?youtube=connection_failed');
        }

        try {
            $tokens = $youtube->exchangeCode($code);
            $channel = $youtube->channel($tokens['access_token']);
            $existingConnection = $request->user()->youtubeConnection()->first();

            $request->user()->youtubeConnection()->updateOrCreate([], [
                'channel_id' => $channel['id'],
                'channel_title' => $channel['title'],
                'access_token' => $tokens['access_token'],
                'refresh_token' => $tokens['refresh_token'] ?: $existingConnection?->refresh_token,
                'token_expires_at' => now()->addSeconds($tokens['expires_in']),
                'scopes' => preg_split('/\s+/', trim($tokens['scope'])) ?: [YoutubeApiClient::SCOPE],
            ]);
        } catch (Throwable) {
            return redirect('/sermons/new?youtube=connection_failed');
        }

        return redirect('/sermons/new?youtube=connected');
    }
}
