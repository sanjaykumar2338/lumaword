<?php

namespace App\Services;

use App\Models\MobilePurchase;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Validation\ValidationException;

class MobilePurchaseVerificationService
{
    public function __construct(
        private readonly ApplePurchaseVerifier $apple,
        private readonly GooglePlayPurchaseVerifier $google,
        private readonly DatabaseManager $database,
    ) {}

    public function verify(User $user, string $store, string $productId, string $transactionId, string $verificationData): MobilePurchase
    {
        $purchase = MobilePurchase::query()->where('store', $store)->where('transaction_id', $transactionId)->first();
        if ($purchase !== null && $purchase->user_id !== $user->id) {
            throw ValidationException::withMessages(['transaction_id' => 'This transaction belongs to another account.']);
        }

        $verifier = $store === 'app_store' ? $this->apple : $this->google;
        $storeKey = $store === 'app_store' ? 'app_store_id' : 'play_store_id';
        $providerProductId = data_get(config("mobile_purchases.products.{$productId}"), $storeKey);
        if (! is_string($providerProductId) || $providerProductId === '') {
            throw ValidationException::withMessages(['product_id' => 'This store product is not configured yet.']);
        }
        $verified = $verifier->verify($providerProductId, $transactionId, $verificationData);

        return $this->database->transaction(fn (): MobilePurchase => MobilePurchase::query()->updateOrCreate(
            ['store' => $store, 'transaction_id' => $transactionId],
            [...$verified, 'product_id' => $productId, 'verification_data' => $verificationData, 'last_verified_at' => now(), 'user_id' => $user->id],
        ));
    }
}
