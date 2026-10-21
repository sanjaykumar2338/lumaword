<?php

namespace App\Services;

use App\Contracts\WebPushGateway;
use App\Data\WebPushDeliveryResult;
use App\Models\PushSubscription as StoredPushSubscription;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class MinishlinkWebPushGateway implements WebPushGateway
{
    public function send(StoredPushSubscription $subscription, string $payload): WebPushDeliveryResult
    {
        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.web_push.vapid_subject'),
                'publicKey' => config('services.web_push.vapid_public_key'),
                'privateKey' => config('services.web_push.vapid_private_key'),
            ],
        ], [
            'TTL' => 3600,
            'urgency' => 'normal',
            'topic' => 'luma-devotional',
            'contentType' => 'application/json',
        ]);
        $report = $webPush->sendOneNotification(new Subscription(
            $subscription->endpoint,
            $subscription->public_key,
            $subscription->auth_token,
            $subscription->content_encoding,
        ), $payload);

        return new WebPushDeliveryResult($report->isSuccess(), $report->isSubscriptionExpired());
    }
}
