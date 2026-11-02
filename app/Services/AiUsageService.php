<?php

namespace App\Services;

use App\Models\AiUsageReservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AiUsageService
{
    public function __construct(private readonly EntitlementService $entitlements) {}

    public function reserve(User $user, string $feature, string $idempotencyKey): AiUsageReservation
    {
        if (! config('ai_usage.enabled') || ! config("ai_usage.feature_enabled.{$feature}")) {
            throw new HttpException(503, 'This AI feature is temporarily unavailable. Please try again later.');
        }

        return DB::transaction(function () use ($user, $feature, $idempotencyKey): AiUsageReservation {
            User::query()->lockForUpdate()->findOrFail($user->id);
            $existing = AiUsageReservation::query()
                ->whereBelongsTo($user)
                ->where('feature', $feature)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing !== null && $existing->status !== 'released') {
                return $existing;
            }

            $periodStart = now()->startOfMonth();
            $periodEnd = now()->addMonthNoOverflow()->startOfMonth();
            $used = AiUsageReservation::query()
                ->whereBelongsTo($user)
                ->where('feature', $feature)
                ->where('period_start', $periodStart)
                ->whereIn('status', ['reserved', 'completed'])
                ->count();

            if ($used >= $this->entitlements->limit($user, $feature)) {
                throw new HttpException(429, 'You have reached this month’s AI allowance. Upgrade to Premium or try again after your allowance resets.');
            }

            $systemCost = (int) AiUsageReservation::query()
                ->where('period_start', $periodStart)
                ->where('status', 'completed')
                ->sum('cost_micros');

            if ($systemCost >= (int) config('ai_usage.system_monthly_cost_limit_micros')) {
                throw new HttpException(503, 'AI generation is temporarily paused for cost protection. Please try again later.');
            }

            if ($existing !== null) {
                $existing->update(['status' => 'reserved', 'period_start' => $periodStart, 'period_end' => $periodEnd, 'released_at' => null]);

                return $existing->refresh();
            }

            return AiUsageReservation::create([
                'user_id' => $user->id,
                'feature' => $feature,
                'idempotency_key' => $idempotencyKey,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
            ]);
        }, 3);
    }

    /** @param array<string, mixed> $usage */
    public function complete(AiUsageReservation $reservation, array $usage = []): void
    {
        $inputTokens = (int) ($usage['input_tokens'] ?? $usage['prompt_tokens'] ?? 0);
        $outputTokens = (int) ($usage['output_tokens'] ?? $usage['completion_tokens'] ?? 0);
        $estimatedCost = (int) ceil(
            ($inputTokens * (int) config('ai_usage.estimated_input_cost_per_million_micros')
                + $outputTokens * (int) config('ai_usage.estimated_output_cost_per_million_micros')) / 1_000_000,
        );

        $reservation->update([
            'status' => 'completed',
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost_micros' => (int) ($usage['cost_micros'] ?? $estimatedCost),
            'completed_at' => now(),
            'released_at' => null,
        ]);
    }

    public function release(AiUsageReservation $reservation): void
    {
        if ($reservation->status === 'completed') {
            return;
        }

        $reservation->update(['status' => 'released', 'released_at' => now()]);
    }

    /** @return array<string, mixed> */
    public function summary(User $user): array
    {
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->addMonthNoOverflow()->startOfMonth();

        return collect((array) config('ai_usage.limits.free'))->keys()->mapWithKeys(function (string $feature) use ($user, $periodStart): array {
            $limit = $this->entitlements->limit($user, $feature);
            $used = AiUsageReservation::query()->whereBelongsTo($user)->where('feature', $feature)
                ->where('period_start', $periodStart)->whereIn('status', ['reserved', 'completed'])->count();

            return [$feature => ['used' => $used, 'limit' => $limit, 'remaining' => max(0, $limit - $used)]];
        })->pipe(fn ($features): array => [
            'plan' => $this->entitlements->plan($user),
            'period_start' => $periodStart->toISOString(),
            'resets_at' => $periodEnd->toISOString(),
            'features' => $features->all(),
        ]);
    }
}
