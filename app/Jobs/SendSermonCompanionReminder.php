<?php

namespace App\Jobs;

use App\Contracts\WebPushGateway;
use App\Models\SermonCompanionReminderDelivery;
use App\Services\NativeNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class SendSermonCompanionReminder implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120];

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly SermonCompanionReminderDelivery $delivery) {}

    /**
     * Execute the job.
     */
    public function handle(WebPushGateway $webPush, NativeNotificationService $native): void
    {
        $delivery = $this->delivery->fresh(['user.notificationPreference', 'user.pushSubscriptions', 'plan.companion']);
        if ($delivery === null || $delivery->sent_at !== null) {
            return;
        }
        $user = $delivery->user;
        $preferences = $user->notificationPreference;
        $enabled = $delivery->kind === 'midweek'
            ? ($preferences?->sermon_companion_midweek_check_ins ?? true)
            : ($preferences?->sermon_companion_reminders ?? true);
        if (! $enabled || ! ($preferences?->enabled ?? true)) {
            $delivery->delete();

            return;
        }
        $payload = ['type' => 'sermon_companion_'.$delivery->kind, 'title' => $delivery->kind === 'midweek' ? 'Midweek reflection' : 'Continue your sermon companion', 'body' => $delivery->kind === 'midweek' ? 'Pause and reflect on what God is teaching you this week.' : 'Your next devotional day is ready when you are.', 'route' => '/sermon-companions/'.$delivery->plan->companion_id];
        $sent = $native->send($user, $payload) > 0;
        if (config('services.web_push.vapid_subject') && config('services.web_push.vapid_public_key') && config('services.web_push.vapid_private_key')) {
            foreach ($user->pushSubscriptions as $subscription) {
                $result = $webPush->send($subscription, json_encode($payload, JSON_THROW_ON_ERROR));
                if ($result->expired) {
                    $subscription->delete();
                } elseif ($result->successful) {
                    $sent = true;
                }
            }
        }
        if (! $sent) {
            throw new RuntimeException('The Sermon Companion reminder could not be delivered.');
        }
        $delivery->update(['sent_at' => now()]);
        if ($delivery->kind === 'midweek') {
            $delivery->plan->progress()->update(['midweek_check_in' => now()]);
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Sermon Companion reminder delivery failed.', ['delivery_id' => $this->delivery->id, 'exception_class' => $exception ? $exception::class : null]);
    }
}
