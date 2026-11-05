<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageReservation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Cashier\Subscription;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $search = trim((string) ($validated['search'] ?? ''));
        $users = $this->summaryQuery()
            ->when($search !== '', fn (Builder $query): Builder => $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest('created_at')
            ->latest('id')
            ->paginate((int) ($validated['per_page'] ?? 20))
            ->withQueryString()
            ->through(fn (User $user): array => $this->presentUser($user));

        return response()->json([
            'data' => $users->items(),
            'links' => [
                'first' => $users->url(1),
                'last' => $users->url($users->lastPage()),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $user = $this->summaryQuery()->findOrFail($user->id);
        $usageByFeature = AiUsageReservation::query()
            ->whereBelongsTo($user)
            ->select('feature')
            ->selectRaw('COUNT(*) as requests')
            ->selectRaw("SUM(CASE WHEN status = 'released' THEN 1 ELSE 0 END) as failed_requests")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN cost_micros ELSE 0 END) as cost_micros")
            ->groupBy('feature')
            ->orderBy('feature')
            ->get()
            ->mapWithKeys(fn (AiUsageReservation $usage): array => [$usage->feature => [
                'requests' => (int) $usage->getAttribute('requests'),
                'failed_requests' => (int) $usage->getAttribute('failed_requests'),
                'estimated_cost_micros' => (int) $usage->getAttribute('cost_micros'),
            ]]);

        return response()->json(['data' => [
            ...$this->presentUser($user),
            'subscriptions' => $user->subscriptions->map(fn (Subscription $subscription): array => [
                'status' => $subscription->stripe_status,
                'stripe_id' => $subscription->stripe_id,
                'stripe_price' => $subscription->stripe_price,
                'trial_ends_at' => $subscription->trial_ends_at?->toISOString(),
                'ends_at' => $subscription->ends_at?->toISOString(),
                'created_at' => $subscription->created_at?->toISOString(),
            ]),
            'usage_by_feature' => $usageByFeature,
        ]]);
    }

    private function summaryQuery(): Builder
    {
        return User::query()
            ->select(['id', 'name', 'email', 'is_admin', 'created_at'])
            ->with(['subscriptions' => fn ($query) => $query
                ->select(['id', 'user_id', 'type', 'stripe_id', 'stripe_status', 'stripe_price', 'trial_ends_at', 'ends_at', 'created_at'])
                ->where('type', config('billing.subscription_name'))
                ->latest('created_at')
                ->latest('id')])
            ->withCount([
                'sermons',
                'bibleStudies',
                'devotionals',
                'aiUsageReservations as ai_requests_count',
                'aiUsageReservations as ai_requests_this_month' => fn (Builder $query): Builder => $query
                    ->where('created_at', '>=', now()->startOfMonth()),
            ])
            ->withSum([
                'aiUsageReservations as estimated_ai_cost_micros' => fn (Builder $query): Builder => $query
                    ->where('status', 'completed'),
            ], 'cost_micros')
            ->withMax('sermons', 'created_at')
            ->withMax('bibleStudies', 'created_at')
            ->withMax('devotionals', 'created_at')
            ->withMax('aiUsageReservations', 'created_at')
            ->withMax('dailyActivities', 'created_at');
    }

    /** @return array<string, mixed> */
    private function presentUser(User $user): array
    {
        $effectiveSubscription = $user->subscriptions->first(fn (Subscription $subscription): bool => in_array($subscription->stripe_status, ['active', 'trialing'], true)
            && ! ($subscription->ends_at?->isPast() ?? false));
        $latestSubscription = $effectiveSubscription ?? $user->subscriptions->first();
        $lastActivity = collect([
            $user->sermons_max_created_at,
            $user->bible_studies_max_created_at,
            $user->devotionals_max_created_at,
            $user->ai_usage_reservations_max_created_at,
            $user->daily_activities_max_created_at,
        ])->filter()->map(fn (string $timestamp): Carbon => Carbon::parse($timestamp))->sortDesc()->first();

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => $user->is_admin,
            'created_at' => $user->created_at?->toISOString(),
            'membership' => $effectiveSubscription === null ? 'free' : 'premium',
            'subscription_status' => $latestSubscription?->stripe_status ?? 'free',
            'subscription_ends_at' => $latestSubscription?->ends_at?->toISOString(),
            'last_activity_at' => $lastActivity?->toISOString(),
            'usage' => [
                'sermons' => (int) $user->sermons_count,
                'bible_studies' => (int) $user->bible_studies_count,
                'devotionals' => (int) $user->devotionals_count,
                'ai_requests' => (int) $user->ai_requests_count,
                'ai_requests_this_month' => (int) $user->ai_requests_this_month,
                'estimated_ai_cost_micros' => (int) ($user->estimated_ai_cost_micros ?? 0),
            ],
        ];
    }
}
