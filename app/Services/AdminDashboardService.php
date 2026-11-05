<?php

namespace App\Services;

use App\Models\AiUsageReservation;
use App\Models\BibleStudy;
use App\Models\DailyActivity;
use App\Models\Devotional;
use App\Models\Sermon;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Cashier\Subscription;

class AdminDashboardService
{
    /** @return array<string, mixed> */
    public function summary(): array
    {
        $today = now()->startOfDay();
        $periodStart = now()->startOfMonth();
        $premiumUsers = $this->premiumUsersQuery()->count();
        $totalUsers = User::query()->count();

        return [
            'generated_at' => now()->toISOString(),
            'metrics' => [
                'total_users' => $totalUsers,
                'registrations_today' => User::query()->where('created_at', '>=', $today)->count(),
                'registrations_7_days' => User::query()->where('created_at', '>=', now()->subDays(6)->startOfDay())->count(),
                'registrations_30_days' => User::query()->where('created_at', '>=', now()->subDays(29)->startOfDay())->count(),
                'free_users' => max(0, $totalUsers - $premiumUsers),
                'premium_users' => $premiumUsers,
                'active_subscriptions' => $this->subscriptionsWithStatus('active')->count(),
                'total_sermons' => Sermon::query()->count(),
                'total_bible_studies' => BibleStudy::query()->count(),
                'total_devotionals' => Devotional::query()->count(),
                'total_ai_requests' => AiUsageReservation::query()->count(),
                'estimated_ai_cost_micros' => (int) AiUsageReservation::query()->where('status', 'completed')->sum('cost_micros'),
            ],
            'registrations' => [
                'last_7_days' => $this->dailySeries(User::query(), 'created_at', 7),
                'last_30_days' => $this->dailySeries(User::query(), 'created_at', 30),
            ],
            'activity' => [
                'last_7_days' => $this->dailySeries(DailyActivity::query(), 'activity_date', 7),
                'last_30_days' => $this->dailySeries(DailyActivity::query(), 'activity_date', 30),
            ],
            'subscriptions' => $this->subscriptionSummary($totalUsers, $premiumUsers),
            'ai_usage' => $this->aiUsageSummary($periodStart, $totalUsers, $premiumUsers),
        ];
    }

    private function premiumUsersQuery(): Builder
    {
        return User::query()->whereHas('subscriptions', function (Builder $query): void {
            $query->where('type', config('billing.subscription_name'))
                ->whereIn('stripe_status', ['active', 'trialing'])
                ->where(fn (Builder $query): Builder => $query->whereNull('ends_at')->orWhere('ends_at', '>', now()));
        });
    }

    private function subscriptionsWithStatus(string $status): Builder
    {
        return Subscription::query()
            ->where('type', config('billing.subscription_name'))
            ->where('stripe_status', $status)
            ->when(in_array($status, ['active', 'trialing'], true), fn (Builder $query): Builder => $query
                ->where(fn (Builder $query): Builder => $query->whereNull('ends_at')->orWhere('ends_at', '>', now())));
    }

    /** @return array<string, mixed> */
    private function subscriptionSummary(int $totalUsers, int $premiumUsers): array
    {
        $recent = Subscription::query()
            ->select(['id', 'user_id', 'type', 'stripe_id', 'stripe_status', 'stripe_price', 'trial_ends_at', 'ends_at', 'created_at'])
            ->with('user:id,name,email')
            ->where('type', config('billing.subscription_name'))
            ->latest('created_at')
            ->latest('id')
            ->limit(25)
            ->get()
            ->map(fn (Subscription $subscription): array => [
                'id' => $subscription->id,
                'user' => [
                    'id' => $subscription->user?->id,
                    'name' => $subscription->user?->name,
                    'email' => $subscription->user?->email,
                ],
                'status' => $subscription->stripe_status,
                'stripe_id' => $subscription->stripe_id,
                'stripe_price' => $subscription->stripe_price,
                'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
                'ends_at' => $subscription->ends_at?->toISOString(),
                'created_at' => $subscription->created_at?->toISOString(),
            ]);

        return [
            'counts' => [
                'active' => $this->subscriptionsWithStatus('active')->count(),
                'trialing' => $this->subscriptionsWithStatus('trialing')->count(),
                'past_due' => $this->subscriptionsWithStatus('past_due')->count(),
                'canceled' => $this->subscriptionsWithStatus('canceled')->count(),
                'free_users' => max(0, $totalUsers - $premiumUsers),
            ],
            'recent' => $recent,
        ];
    }

    /** @return array<string, mixed> */
    private function aiUsageSummary(CarbonInterface $periodStart, int $totalUsers, int $premiumUsers): array
    {
        $usageByFeature = AiUsageReservation::query()
            ->select('feature')
            ->selectRaw('COUNT(*) as requests')
            ->selectRaw("SUM(CASE WHEN status = 'released' THEN 1 ELSE 0 END) as failed_requests")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN cost_micros ELSE 0 END) as cost_micros")
            ->where('created_at', '>=', $periodStart)
            ->groupBy('feature')
            ->orderBy('feature')
            ->get()
            ->keyBy('feature');

        $freeUsers = max(0, $totalUsers - $premiumUsers);
        $quota = collect((array) config('ai_usage.limits.free'))->map(function (int $freeLimit, string $feature) use ($freeUsers, $premiumUsers): array {
            $premiumLimit = (int) config("ai_usage.limits.premium.{$feature}", 0);
            $used = AiUsageReservation::query()
                ->where('feature', $feature)
                ->where('period_start', now()->startOfMonth())
                ->whereIn('status', ['reserved', 'completed'])
                ->count();
            $available = ($freeUsers * $freeLimit) + ($premiumUsers * $premiumLimit);

            return [
                'used' => $used,
                'available' => $available,
                'percent' => $available > 0 ? round(($used / $available) * 100, 1) : 0,
            ];
        });

        return [
            'requests_today' => AiUsageReservation::query()->where('created_at', '>=', now()->startOfDay())->count(),
            'requests_this_month' => AiUsageReservation::query()->where('created_at', '>=', $periodStart)->count(),
            'failed_requests_this_month' => AiUsageReservation::query()->where('created_at', '>=', $periodStart)->where('status', 'released')->count(),
            'estimated_cost_micros_this_month' => (int) AiUsageReservation::query()->where('created_at', '>=', $periodStart)->where('status', 'completed')->sum('cost_micros'),
            'by_feature' => $usageByFeature->map(fn (AiUsageReservation $usage): array => [
                'requests' => (int) $usage->getAttribute('requests'),
                'failed_requests' => (int) $usage->getAttribute('failed_requests'),
                'estimated_cost_micros' => (int) $usage->getAttribute('cost_micros'),
            ]),
            'quota_utilization' => $quota,
            'monthly_trends' => $this->monthlyAiTrends(6),
        ];
    }

    /** @return array<int, array{date: string, count: int}> */
    private function dailySeries(Builder $query, string $column, int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();
        $threshold = $column === 'activity_date' ? $start->toDateString() : $start;
        $counts = $query
            ->selectRaw("DATE({$column}) as aggregate_date, COUNT(*) as aggregate_count")
            ->where($column, '>=', $threshold)
            ->groupBy('aggregate_date')
            ->pluck('aggregate_count', 'aggregate_date');

        return collect(range(0, $days - 1))->map(function (int $offset) use ($start, $counts): array {
            $date = $start->copy()->addDays($offset)->toDateString();

            return ['date' => $date, 'count' => (int) ($counts[$date] ?? 0)];
        })->all();
    }

    /** @return array<int, array{month: string, requests: int, failed_requests: int, estimated_cost_micros: int}> */
    private function monthlyAiTrends(int $months): array
    {
        $firstMonth = now()->startOfMonth()->subMonths($months - 1);

        return collect(range(0, $months - 1))->map(function (int $offset) use ($firstMonth): array {
            $start = $firstMonth->copy()->addMonths($offset);
            $end = $start->copy()->addMonth();
            $query = AiUsageReservation::query()->where('created_at', '>=', $start)->where('created_at', '<', $end);

            return [
                'month' => $start->format('Y-m'),
                'requests' => (clone $query)->count(),
                'failed_requests' => (clone $query)->where('status', 'released')->count(),
                'estimated_cost_micros' => (int) (clone $query)->where('status', 'completed')->sum('cost_micros'),
            ];
        })->all();
    }
}
