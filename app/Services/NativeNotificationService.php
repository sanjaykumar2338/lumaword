<?php

namespace App\Services;

use App\Contracts\NativeNotificationGateway;
use App\Models\User;

class NativeNotificationService
{
    public function __construct(private readonly NativeNotificationGateway $gateway) {}

    /** @param array{type: string, title: string, body: string, route?: string, route_arguments?: array<string, mixed>} $payload */
    public function send(User $user, array $payload): int
    {
        if (! config('services.firebase.enabled') || ! ($user->notificationPreference?->enabled ?? true)) {
            return 0;
        }

        $sent = 0;
        foreach ($user->deviceTokens()->get() as $deviceToken) {
            $sent += (int) $this->gateway->send($deviceToken, $payload);
        }

        return $sent;
    }
}
