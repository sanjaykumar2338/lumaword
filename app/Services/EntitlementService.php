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
        $subscription = $user->subscription((string) config('billing.subscription_name'));

        return [
            'plan' => $this->plan($user),
            'status' => $subscription?->stripe_status ?? 'free',
            'renews_or_ends_at' => $subscription?->ends_at?->toISOString(),
            'on_grace_period' => $subscription?->onGracePeriod() ?? false,
            'billing_enabled' => (bool) config('billing.enabled'),
        ];
    }
}
