<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePushSubscriptionRequest;
use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request): JsonResponse
    {
        $endpoint = $request->validated('endpoint');
        $endpointHash = hash('sha256', $endpoint);
        $existing = PushSubscription::query()->where('endpoint_hash', $endpointHash)->first();

        abort_if($existing !== null && $existing->user_id !== $request->user()->id, 409, 'This browser subscription belongs to another account.');

        $subscription = $request->user()->pushSubscriptions()->updateOrCreate(
            ['endpoint_hash' => $endpointHash],
            [
                'endpoint' => $endpoint,
                'public_key' => $request->validated('keys.p256dh'),
                'auth_token' => $request->validated('keys.auth'),
                'content_encoding' => $request->validated('content_encoding', 'aes128gcm'),
            ],
        );

        return response()->json([
            'data' => ['id' => $subscription->id],
            'message' => 'Browser notifications enabled.',
        ], $subscription->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate(['endpoint' => ['required', 'url:https', 'max:2048']]);
        $request->user()->pushSubscriptions()
            ->where('endpoint_hash', hash('sha256', $validated['endpoint']))
            ->delete();

        return response()->json(['message' => 'Browser notifications disabled.']);
    }
}
