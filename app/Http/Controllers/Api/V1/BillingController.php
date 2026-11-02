<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\EntitlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

class BillingController extends Controller
{
    public function show(Request $request, EntitlementService $entitlements): JsonResponse
    {
        return response()->json(['data' => $entitlements->subscription($request->user())]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $data = $request->validate(['interval' => ['required', Rule::in(['monthly', 'annual'])]]);

        if (! config('billing.enabled')) {
            throw new HttpException(503, 'Billing is not enabled yet.');
        }

        $price = config("billing.prices.{$data['interval']}.stripe_id");
        if (! is_string($price) || $price === '') {
            throw new HttpException(503, 'This billing option is not configured yet.');
        }

        if ($request->user()->subscribed((string) config('billing.subscription_name'))) {
            throw new HttpException(409, 'You already have an active Premium subscription.');
        }

        $checkout = $request->user()->newSubscription((string) config('billing.subscription_name'), $price)->checkout([
            'success_url' => route('settings').'?billing=success',
            'cancel_url' => route('pricing').'?billing=cancelled',
        ]);

        return response()->json(['url' => $checkout->url]);
    }

    public function portal(Request $request): JsonResponse
    {
        if ($request->user()->stripe_id === null) {
            throw new HttpException(409, 'No billing account is available for this user.');
        }

        return response()->json(['url' => $request->user()->billingPortalUrl(route('settings'))]);
    }
}
