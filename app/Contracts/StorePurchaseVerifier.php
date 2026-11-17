<?php

namespace App\Contracts;

interface StorePurchaseVerifier
{
    /** @return array{status: string, product_id: string, transaction_id: string, original_transaction_id: ?string, purchased_at: ?string, expires_at: ?string, response: array<string, mixed>} */
    public function verify(string $productId, string $transactionId, string $verificationData): array;
}
