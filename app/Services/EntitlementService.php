<?php

namespace App\Services;

use App\Models\User;

class EntitlementService
{
    public function plan(User $user): string
    {
        return $this->isPremium($user) ? 'premium' : 'free';
    }

    public function isPremium(User $user): bool
    {
        $mobilePurchase = $user->mobilePurchases()
            ->whereIn('status', ['active', 'grace_period', 'canceled'])
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($mobilePurchase) {
            return true;
        }

        if ($user->manual_premium_until?->isFuture() ?? false) {
            return true;
        }

        $subscription = $user->subscription((string) config('billing.subscription_name'));

        if ($subscription === null) {
            return false;
        }

        return in_array($subscription->stripe_status, ['active', 'trialing'], true)
            && ! ($subscription->ends_at?->isPast() ?? false);
    }

    public function limit(User $user, string $feature): int
    {
        return (int) config("ai_usage.limits.{$this->plan($user)}.{$feature}", 0);
    }

    /** @return array<string, mixed> */
    public function subscription(User $user): array
    {
        $mobilePurchase = $user->mobilePurchases()
            ->whereIn('status', ['active', 'grace_period', 'canceled'])
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest('expires_at')
            ->first();
        $subscription = $user->subscription((string) config('billing.subscription_name'));
        $status = $mobilePurchase?->status ?? ($user->manual_premium_until?->isFuture() ? 'manual' : $subscription?->stripe_status ?? 'free');
        $renewsOrEndsAt = $mobilePurchase?->expires_at?->toISOString()
            ?? ($subscription?->trial_ends_at ?? $subscription?->ends_at)?->toISOString();

        return [
            'plan' => $this->plan($user),
            'status' => $status,
            'renews_or_ends_at' => $renewsOrEndsAt,
            'on_grace_period' => $mobilePurchase === null && ($subscription?->onGracePeriod() ?? false),
            'source' => $mobilePurchase !== null ? 'mobile_purchase' : ($user->manual_premium_until?->isFuture() ? 'manual' : ($subscription !== null ? 'stripe' : null)),
            'canceled' => in_array($status, ['canceled', 'cancelled'], true) || ($subscription?->onGracePeriod() ?? false),
            'billing_enabled' => (bool) config('billing.enabled'),
        ];
    }
}
