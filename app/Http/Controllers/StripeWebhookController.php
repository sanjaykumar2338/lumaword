<?php

namespace App\Http\Controllers;

use App\Models\StripeWebhookEvent;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class StripeWebhookController extends WebhookController
{
    public function handleWebhook(Request $request): Response
    {
        $payload = $request->json()->all();
        $eventId = (string) ($payload['id'] ?? '');
        $subjectId = data_get($payload, 'data.object.id');
        abort_if($eventId === '', 400, 'Stripe event identifier is missing.');

        $existing = StripeWebhookEvent::query()->where('stripe_event_id', $eventId)->first();
        if ($existing?->processed_at !== null || $existing?->status === 'ignored') {
            return new Response('Webhook already handled', 200);
        }

        if (is_string($subjectId) && StripeWebhookEvent::query()->where('subject_id', $subjectId)
            ->where('stripe_created_at', '>', (int) ($payload['created'] ?? 0))
            ->whereIn('status', ['processed', 'ignored'])->exists()) {
            StripeWebhookEvent::query()->updateOrCreate(['stripe_event_id' => $eventId], [
                'type' => (string) ($payload['type'] ?? 'unknown'), 'subject_id' => $subjectId,
                'stripe_created_at' => (int) ($payload['created'] ?? 0), 'status' => 'ignored', 'processed_at' => now(),
            ]);

            return new Response('Out-of-order webhook ignored', 200);
        }

        $event = StripeWebhookEvent::query()->updateOrCreate(['stripe_event_id' => $eventId], [
            'type' => (string) ($payload['type'] ?? 'unknown'), 'subject_id' => is_string($subjectId) ? $subjectId : null,
            'stripe_created_at' => (int) ($payload['created'] ?? 0), 'status' => 'processing',
        ]);

        try {
            $response = parent::handleWebhook($request);
            $event->update(['status' => 'processed', 'processed_at' => now()]);

            return $response;
        } catch (Throwable $exception) {
            $event->update(['status' => 'failed']);
            throw $exception;
        }
    }
}
