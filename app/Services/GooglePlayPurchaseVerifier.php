<?php

namespace App\Services;

use App\Contracts\StorePurchaseVerifier;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class GooglePlayPurchaseVerifier implements StorePurchaseVerifier
{
    public function verify(string $productId, string $transactionId, string $verificationData): array
    {
        if (! config('mobile_purchases.play_store.enabled')) {
            throw ValidationException::withMessages(['store' => 'Google Play purchase verification is not configured yet.']);
        }

        $url = sprintf('https://androidpublisher.googleapis.com/androidpublisher/v3/applications/%s/purchases/subscriptionsv2/tokens/%s', rawurlencode((string) config('mobile_purchases.play_store.package_name')), rawurlencode($verificationData));
        $response = Http::withToken((string) config('mobile_purchases.play_store.access_token'))->connectTimeout(3)->timeout(10)->get($url)->throw();
        $purchase = $response->json();
        $line = collect($purchase['lineItems'] ?? [])->first(fn (array $item): bool => ($item['productId'] ?? null) === $productId);
        if (! is_array($line) || ($purchase['subscriptionState'] ?? null) === 'SUBSCRIPTION_STATE_EXPIRED') {
            throw ValidationException::withMessages(['verification_data' => 'Google Play rejected this transaction.']);
        }

        return [
            'status' => ($purchase['subscriptionState'] ?? null) === 'SUBSCRIPTION_STATE_ACTIVE' ? 'active' : 'grace_period',
            'product_id' => $productId, 'transaction_id' => $transactionId, 'original_transaction_id' => null,
            'purchased_at' => null, 'expires_at' => isset($line['expiryTime']) ? $line['expiryTime'] : null,
            'response' => $purchase,
        ];
    }
}
