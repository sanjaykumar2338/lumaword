<?php

namespace App\Services;

use App\DailyActivityType;
use App\Models\DailyActivity;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DailyActivityRecorder
{
    public function record(User $user, DailyActivityType $type, ?string $timezone = null): DailyActivity
    {
        $activityTimezone = $timezone ?? $user->devotionalReminderPreference()->value('timezone') ?? 'UTC';
        $activityDate = now($activityTimezone)->toDateString();
        $storedDate = CarbonImmutable::parse($activityDate, $activityTimezone)->startOfDay();

        return DB::transaction(function () use ($user, $type, $activityTimezone, $storedDate): DailyActivity {
            $activity = $user->dailyActivities()->firstOrCreate(
                ['activity_date' => $storedDate],
                ['timezone' => $activityTimezone],
            );

            if (! $activity->{$type->value}) {
                $activity->forceFill([$type->value => true])->save();
            }

            return $activity->refresh();
        });
    }
}
