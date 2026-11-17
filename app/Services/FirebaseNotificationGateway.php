<?php

namespace App\Services;

use App\Contracts\NativeNotificationGateway;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Http;

class FirebaseNotificationGateway implements NativeNotificationGateway
{
    public function send(DeviceToken $deviceToken, array $payload): bool
    {
        if (! config('services.firebase.enabled')) {
            return false;
        }

        return Http::withToken((string) config('services.firebase.server_key'))
            ->connectTimeout(3)->timeout(10)
            ->post((string) config('services.firebase.endpoint'), [
                'to' => $deviceToken->token,
                'notification' => ['title' => $payload['title'], 'body' => $payload['body']],
                'data' => collect($payload)->except(['title', 'body'])->map(
                    fn (mixed $value): string => is_scalar($value) ? (string) $value : (string) json_encode($value),
                )->all(),
            ])->successful();
    }
}
