<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateDevotionalReminderPreferenceRequest;
use App\Models\DevotionalReminderPreference;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DevotionalReminderPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $preference = $request->user()->devotionalReminderPreference()->first();
        $timezoneConfigured = $preference !== null;
        $preference ??= new DevotionalReminderPreference([
            'enabled' => false,
            'reminder_time' => '08:00',
            'timezone' => 'UTC',
        ]);

        return response()->json(['data' => $this->preferenceData($request, $preference, $timezoneConfigured)]);
    }

    public function update(UpdateDevotionalReminderPreferenceRequest $request): JsonResponse
    {
        $preference = $request->user()->devotionalReminderPreference()->updateOrCreate([], $request->validated());

        return response()->json([
            'data' => $this->preferenceData($request, $preference, true),
            'message' => 'Devotional reminder preferences updated.',
        ]);
    }

    /** @return array<string, mixed> */
    private function preferenceData(Request $request, DevotionalReminderPreference $preference, bool $timezoneConfigured): array
    {
        return [
            'enabled' => $preference->enabled,
            'reminder_time' => substr($preference->reminder_time, 0, 5),
            'timezone' => $preference->timezone,
            'timezone_configured' => $timezoneConfigured,
            'timezone_options' => $this->timezoneOptions(),
            'push_supported' => filled(config('services.web_push.vapid_public_key')),
            'vapid_public_key' => config('services.web_push.vapid_public_key'),
            'subscription_count' => $request->user()->pushSubscriptions()->count(),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function timezoneOptions(): array
    {
        return array_map(
            fn (string $timezone): array => [
                'value' => $timezone,
                'label' => sprintf('(UTC%s) %s', CarbonImmutable::now($timezone)->format('P'), $timezone),
            ],
            DateTimeZone::listIdentifiers(DateTimeZone::ALL),
        );
    }
}
