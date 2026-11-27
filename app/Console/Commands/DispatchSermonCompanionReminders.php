<?php

namespace App\Console\Commands;

use App\Jobs\SendSermonCompanionReminder;
use App\Models\SermonCompanionDevotionalPlan;
use App\Models\SermonCompanionReminderDelivery;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\UniqueConstraintViolationException;

#[Signature('app:dispatch-sermon-companion-reminders')]
#[Description('Queue due Sermon Companion reminders in each user timezone')]
class DispatchSermonCompanionReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hasWebPush = filled(config('services.web_push.vapid_subject')) && filled(config('services.web_push.vapid_public_key')) && filled(config('services.web_push.vapid_private_key'));
        if (! $hasWebPush && ! config('services.firebase.enabled')) {
            $this->components->warn('No push provider is configured; no Sermon Companion reminders were queued.');

            return self::SUCCESS;
        }
        $queued = 0;
        SermonCompanionDevotionalPlan::query()->where('status', 'completed')->with(['companion', 'progress', 'companion.user.notificationPreference', 'companion.user.pushSubscriptions', 'companion.user.deviceTokens'])->chunkById(100, function ($plans) use (&$queued): void {
            foreach ($plans as $plan) {
                $user = $plan->companion->user;
                $timezone = $user->notificationPreference?->timezone ?? 'UTC';
                $localNow = CarbonImmutable::now($timezone);
                $time = substr((string) ($user->notificationPreference?->reminder_time ?? '08:00'), 0, 5);
                if ($localNow->format('H:i') < $time) {
                    continue;
                }
                $completed = (array) ($plan->progress?->completed_days ?? []);
                if (($user->notificationPreference?->sermon_companion_reminders ?? true) && count($completed) < $plan->days_count && ! in_array($plan->progress?->current_day, $completed, true)) {
                    $delivery = $this->delivery($plan, 'daily', $localNow->toDateString(), $user->id);
                    if ($delivery->wasRecentlyCreated) {
                        SendSermonCompanionReminder::dispatch($delivery);
                        $queued++;
                    }
                }
                if (($user->notificationPreference?->sermon_companion_midweek_check_ins ?? true) && $plan->days_count >= 5 && count($completed) >= 2 && $plan->progress?->midweek_check_in === null) {
                    $delivery = $this->delivery($plan, 'midweek', $localNow->toDateString(), $user->id);
                    if ($delivery->wasRecentlyCreated) {
                        SendSermonCompanionReminder::dispatch($delivery);
                        $queued++;
                    }
                }
            }
        });
        $this->components->info("Queued {$queued} Sermon Companion reminder(s).");

        return self::SUCCESS;
    }

    private function delivery(SermonCompanionDevotionalPlan $plan, string $kind, string $date, int $userId): SermonCompanionReminderDelivery
    {
        $delivery = $plan->reminderDeliveries()->where('kind', $kind)->whereDate('reminder_date', $date)->first();
        if ($delivery !== null) {
            return $delivery;
        }
        try {
            return $plan->reminderDeliveries()->create(['user_id' => $userId, 'kind' => $kind, 'reminder_date' => $date]);
        } catch (UniqueConstraintViolationException) {
            return $plan->reminderDeliveries()->where('kind', $kind)->whereDate('reminder_date', $date)->firstOrFail();
        }
    }
}
