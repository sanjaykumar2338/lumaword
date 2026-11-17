<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateNotificationPreferenceRequest;
use App\Models\NotificationPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $preference = $request->user()->notificationPreference()->first();

        return response()->json(['data' => $this->data($preference ?? new NotificationPreference)]);
    }

    public function update(UpdateNotificationPreferenceRequest $request): JsonResponse
    {
        $preference = $request->user()->notificationPreference()->updateOrCreate([], $request->validated());

        return response()->json(['data' => $this->data($preference), 'message' => 'Notification preferences updated.']);
    }

    /** @return array<string, bool> */
    private function data(NotificationPreference $preference): array
    {
        return [
            'enabled' => $preference->enabled ?? true,
            'sermon_processing' => $preference->sermon_processing ?? true,
            'devotional_reminders' => $preference->devotional_reminders ?? true,
            'subscription_updates' => $preference->subscription_updates ?? true,
        ];
    }
}
