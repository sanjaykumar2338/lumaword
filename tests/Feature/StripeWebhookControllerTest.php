<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class StripeWebhookControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_403_for_invalid_signature(): void
    {
        config()->set('cashier.webhook.secret', 'whsec_test');

        $this->withHeader('Stripe-Signature', 'invalid')->postJson('/stripe/webhook', ['id' => 'evt_invalid'])->assertForbidden();
        $this->assertDatabaseCount('stripe_webhook_events', 0);
    }

    public function test_duplicate_delivery_is_processed_once(): void
    {
        config()->set('cashier.webhook.secret', 'whsec_test');
        $payload = json_encode(['id' => 'evt_duplicate', 'type' => 'luma.test', 'created' => 1700000000,
            'data' => ['object' => ['id' => 'obj_1']]], JSON_THROW_ON_ERROR);
        $timestamp = time();
        $signature = "t={$timestamp},v1=".hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_test');

        $server = ['CONTENT_TYPE' => 'application/json', 'HTTP_STRIPE_SIGNATURE' => $signature];
        $this->call('POST', '/stripe/webhook', [], [], [], $server, $payload)->assertOk();
        $this->call('POST', '/stripe/webhook', [], [], [], $server, $payload)->assertOk();

        $this->assertDatabaseCount('stripe_webhook_events', 1);
        $this->assertDatabaseHas('stripe_webhook_events', ['stripe_event_id' => 'evt_duplicate', 'status' => 'processed']);
    }

    public function test_older_event_for_same_subject_is_ignored_after_newer_event(): void
    {
        config()->set('cashier.webhook.secret', 'whsec_test');
        $newer = json_encode(['id' => 'evt_newer', 'type' => 'luma.test', 'created' => 1700000100,
            'data' => ['object' => ['id' => 'sub_subject']]], JSON_THROW_ON_ERROR);
        $older = json_encode(['id' => 'evt_older', 'type' => 'luma.test', 'created' => 1700000000,
            'data' => ['object' => ['id' => 'sub_subject']]], JSON_THROW_ON_ERROR);

        $this->signedWebhook($newer)->assertOk();
        $this->signedWebhook($older)->assertOk()->assertSeeText('Out-of-order webhook ignored');

        $this->assertDatabaseHas('stripe_webhook_events', ['stripe_event_id' => 'evt_newer', 'status' => 'processed']);
        $this->assertDatabaseHas('stripe_webhook_events', ['stripe_event_id' => 'evt_older', 'status' => 'ignored']);
    }

    private function signedWebhook(string $payload): TestResponse
    {
        $timestamp = time();
        $signature = "t={$timestamp},v1=".hash_hmac('sha256', "{$timestamp}.{$payload}", 'whsec_test');

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $payload);
    }
}
