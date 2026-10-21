<?php

namespace App\Jobs;

use App\Contracts\WebPushGateway;
use App\Models\DevotionalReminderDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

class SendDevotionalReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(public DevotionalReminderDelivery $delivery) {}

    /**
     * Execute the job.
     */
    public function handle(WebPushGateway $gateway): void
    {
        $delivery = $this->delivery->fresh(['user.devotionalReminderPreference', 'user.pushSubscriptions']);

        if ($delivery === null || $delivery->sent_at !== null) {
            return;
        }

        $user = $delivery->user;
        $preference = $user->devotionalReminderPreference;
        $reminderDate = $delivery->reminder_date->toDateString();

        if ($preference === null || ! $preference->enabled) {
            $delivery->delete();

            return;
        }

        if ($user->dailyActivities()->whereDate('activity_date', $reminderDate)->where('devotional_read', true)->exists()) {
            $delivery->delete();

            return;
        }

        $payload = json_encode([
            'title' => 'Your daily moment with Luma',
            'body' => 'Take a few minutes to pause, reflect, and spend time in God’s Word today.',
            'url' => '/devotionals',
        ], JSON_THROW_ON_ERROR);
        $sent = false;
        $retryableFailure = false;

        foreach ($user->pushSubscriptions as $subscription) {
            $result = $gateway->send($subscription, $payload);

            if ($result->expired) {
                $subscription->delete();
            } elseif ($result->successful) {
                $sent = true;
            } else {
                $retryableFailure = true;
            }
        }

        if ($sent) {
            $delivery->update(['sent_at' => now()]);

            return;
        }

        if ($retryableFailure) {
            throw new RuntimeException('The devotional reminder could not be delivered.');
        }

        $delivery->delete();
    }
}
