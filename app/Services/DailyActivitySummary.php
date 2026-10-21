<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;

class DailyActivitySummary
{
    /** @return array{current_streak: int, longest_streak: int, total_active_days: int, timezone: string, last_seven_days: list<array{date: string, label: string, active: bool, is_today: bool}>} */
    public function forUser(User $user): array
    {
        $timezone = $user->devotionalReminderPreference?->timezone ?? 'UTC';
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $dates = $user->dailyActivities()
            ->orderBy('activity_date')
            ->pluck('activity_date')
            ->map(fn ($date): string => CarbonImmutable::parse($date)->toDateString())
            ->unique()
            ->values();
        $activeDates = array_fill_keys($dates->all(), true);

        return [
            'current_streak' => $this->currentStreak($activeDates, $today),
            'longest_streak' => $this->longestStreak($dates->all()),
            'total_active_days' => $dates->count(),
            'timezone' => $timezone,
            'last_seven_days' => collect(range(6, 0))->map(function (int $daysAgo) use ($today, $activeDates): array {
                $date = $today->subDays($daysAgo);

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->format('D'),
                    'active' => isset($activeDates[$date->toDateString()]),
                    'is_today' => $daysAgo === 0,
                ];
            })->all(),
        ];
    }

    /** @param array<string, bool> $activeDates */
    private function currentStreak(array $activeDates, CarbonImmutable $today): int
    {
        $cursor = isset($activeDates[$today->toDateString()]) ? $today : $today->subDay();
        $streak = 0;

        while (isset($activeDates[$cursor->toDateString()])) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    /** @param list<string> $dates */
    private function longestStreak(array $dates): int
    {
        $longest = 0;
        $current = 0;
        $previous = null;

        foreach ($dates as $date) {
            $parsed = CarbonImmutable::parse($date);
            $current = $previous !== null && $previous->addDay()->isSameDay($parsed) ? $current + 1 : 1;
            $longest = max($longest, $current);
            $previous = $parsed;
        }

        return $longest;
    }
}
