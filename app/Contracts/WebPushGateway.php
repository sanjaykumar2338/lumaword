<?php

namespace App\Contracts;

use App\Data\WebPushDeliveryResult;
use App\Models\PushSubscription;

interface WebPushGateway
{
    public function send(PushSubscription $subscription, string $payload): WebPushDeliveryResult;
}
