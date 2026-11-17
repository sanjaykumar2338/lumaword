<?php

namespace App\Contracts;

use App\Models\DeviceToken;

interface NativeNotificationGateway
{
    /** @param array<string, mixed> $payload */
    public function send(DeviceToken $deviceToken, array $payload): bool;
}
