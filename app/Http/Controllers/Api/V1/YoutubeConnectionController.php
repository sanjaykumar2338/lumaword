<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\YoutubeApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class YoutubeConnectionController extends Controller
{
    public function show(Request $request, YoutubeApiClient $youtube): JsonResponse
    {
        $connection = $request->user()->youtubeConnection()->first();

        return response()->json([
            'configured' => $youtube->configured(),
            'connected' => $connection !== null,
            'channel' => $connection ? [
                'id' => $connection->channel_id,
                'title' => $connection->channel_title,
            ] : null,
        ]);
    }

    public function store(Request $request, YoutubeApiClient $youtube): JsonResponse
    {
        if (! $youtube->configured()) {
            return response()->json([
                'message' => 'YouTube connection is not configured for this Luma installation.',
            ], 503);
        }

        $state = Str::random(64);
        $request->session()->put('youtube_oauth_state', $state);

        return response()->json(['authorization_url' => $youtube->authorizationUrl($state)]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->youtubeConnection()->delete();

        return response()->json(['message' => 'YouTube channel disconnected.']);
    }
}
