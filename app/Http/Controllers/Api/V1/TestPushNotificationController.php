<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\WebPushGateway;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class TestPushNotificationController extends Controller
{
    public function __invoke(Request $request, WebPushGateway $gateway): JsonResponse
    {
        $validated = $request->validate(['endpoint' => ['required', 'url:https', 'max:2048']]);
        $subscription = $request->user()->pushSubscriptions()
            ->where('endpoint_hash', hash('sha256', $validated['endpoint']))
            ->first();

        if ($subscription === null) {
            return response()->json([
                'message' => 'No active push subscription was found for this browser.',
            ], 404);
        }

        $payload = json_encode([
            'title' => 'Luma Test Notification',
            'body' => 'Your browser notifications are working correctly!',
            'url' => '/devotionals',
        ], JSON_THROW_ON_ERROR);

        try {
            $result = $gateway->send($subscription, $payload);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The test notification could not be delivered. Please try again.',
            ], 502);
        }

        if ($result->expired) {
            $subscription->delete();

            return response()->json([
                'message' => 'This browser’s push subscription has expired. Enable browser notifications again and retry.',
            ], 410);
        }

        if (! $result->successful) {
            return response()->json([
                'message' => 'The test notification could not be delivered. Please try again.',
            ], 502);
        }

        return response()->json(['message' => 'Test notification sent successfully.']);
    }
}
