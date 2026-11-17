<?php

namespace App\Services;

use App\Contracts\NativeNotificationGateway;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FirebaseNotificationGateway implements NativeNotificationGateway
{
    public function send(DeviceToken $deviceToken, array $payload): bool
    {
        if (! config('services.firebase.enabled')) {
            return false;
        }

        $accessToken = $this->accessToken();
        if ($accessToken === null) {
            return false;
        }

        $endpoint = sprintf((string) config('services.firebase.endpoint'), config('services.firebase.project_id'));

        return Http::withToken($accessToken)
            ->connectTimeout(3)->timeout(10)
            ->post($endpoint, ['message' => [
                'token' => $deviceToken->token,
                'notification' => ['title' => $payload['title'], 'body' => $payload['body']],
                'data' => collect($payload)->except(['title', 'body'])->map(
                    fn (mixed $value): string => is_scalar($value) ? (string) $value : (string) json_encode($value),
                )->all(),
            ]])->successful();
    }

    private function accessToken(): ?string
    {
        return Cache::remember('firebase.access_token', now()->addMinutes(50), function (): ?string {
            $credentials = json_decode((string) config('services.firebase.service_account_json'), true);
            if (! is_array($credentials) || blank($credentials['client_email'] ?? null) || blank($credentials['private_key'] ?? null)) {
                return null;
            }

            $now = now()->timestamp;
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64Url(json_encode([
                'iss' => $credentials['client_email'], 'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token', 'iat' => $now, 'exp' => $now + 3600,
            ]));
            $unsigned = $header.'.'.$claims;
            if (! openssl_sign($unsigned, $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                return null;
            }

            $response = Http::asForm()->connectTimeout(3)->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $unsigned.'.'.$this->base64Url($signature),
            ]);

            return $response->successful() && is_string($response->json('access_token'))
                ? $response->json('access_token')
                : null;
        });
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
