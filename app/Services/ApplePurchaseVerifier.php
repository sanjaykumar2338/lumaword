<?php

namespace App\Services;

use App\Contracts\StorePurchaseVerifier;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class ApplePurchaseVerifier implements StorePurchaseVerifier
{
    public function verify(string $productId, string $transactionId, string $verificationData): array
    {
        if (! config('mobile_purchases.app_store.enabled')) {
            throw ValidationException::withMessages(['store' => 'Apple purchase verification is not configured yet.']);
        }

        $payload = ['receipt-data' => $verificationData, 'password' => config('mobile_purchases.app_store.shared_secret')];
        $response = Http::connectTimeout(3)->timeout(10)->post('https://buy.itunes.apple.com/verifyReceipt', $payload);
        if ($response->json('status') === 21007) {
            $response = Http::connectTimeout(3)->timeout(10)->post('https://sandbox.itunes.apple.com/verifyReceipt', $payload);
        }

        $response->throw();
        $receipt = $response->json();
        $transaction = collect($receipt['latest_receipt_info'] ?? [])->first(fn (array $item): bool => ($item['transaction_id'] ?? null) === $transactionId && ($item['product_id'] ?? null) === $productId);
        if (! is_array($transaction)) {
            throw ValidationException::withMessages(['verification_data' => 'Apple rejected this transaction.']);
        }

        return [
            'status' => isset($transaction['expires_date_ms']) && (int) $transaction['expires_date_ms'] > now()->valueOf() ? 'active' : 'expired',
            'product_id' => $productId, 'transaction_id' => $transactionId,
            'original_transaction_id' => $transaction['original_transaction_id'] ?? null,
            'purchased_at' => isset($transaction['purchase_date_ms']) ? now()->createFromTimestampMs((int) $transaction['purchase_date_ms'])->toISOString() : null,
            'expires_at' => isset($transaction['expires_date_ms']) ? now()->createFromTimestampMs((int) $transaction['expires_date_ms'])->toISOString() : null,
            'response' => $receipt,
        ];
    }
}
