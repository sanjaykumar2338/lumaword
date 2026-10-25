<?php

namespace App\Console\Commands;

use App\Jobs\SendDevotionalReminder;
use App\Models\DevotionalReminderPreference;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('devotional-reminders:dispatch')]
#[Description('Queue due devotional reminders in each user’s local timezone')]
class DispatchDevotionalReminders extends Command
{
    public function handle(): int
    {
        if (blank(config('services.web_push.vapid_subject'))
            || blank(config('services.web_push.vapid_public_key'))
            || blank(config('services.web_push.vapid_private_key'))) {
            $this->components->warn('Web Push is not configured; no reminders were queued.');

            return self::SUCCESS;
        }

        $queued = 0;

        DevotionalReminderPreference::query()
            ->where('enabled', true)
            ->whereHas('user.pushSubscriptions')
            ->with('user')
            ->chunkById(100, function ($preferences) use (&$queued): void {
                foreach ($preferences as $preference) {
                    $localNow = CarbonImmutable::now($preference->timezone);

                    if ($localNow->format('H:i') < substr($preference->reminder_time, 0, 5)) {
                        continue;
                    }

                    $date = $localNow->toDateString();
                    $completedDevotional = $preference->user->dailyActivities()
                        ->whereDate('activity_date', $date)
                        ->where('devotional_read', true)
                        ->exists();

                    if ($completedDevotional) {
                        continue;
                    }

                    $delivery = $preference->user->devotionalReminderDeliveries()->firstOrCreate([
                        'reminder_date' => $localNow->startOfDay(),
                    ]);

                    if ($delivery->wasRecentlyCreated) {
                        SendDevotionalReminder::dispatch($delivery);
                        $queued++;
                    }
                }
            });

        $this->components->info("Queued {$queued} devotional reminder(s).");

        return self::SUCCESS;
    }
}
