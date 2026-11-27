<?php

namespace App\Jobs;

use App\Models\AiUsageReservation;
use App\Models\SermonCompanionDevotionalPlan;
use App\Models\SermonCompanionProgress;
use App\Services\AiUsageService;
use App\Services\OpenAiSermonCompanionGenerator;
use App\Services\SermonCompanionScriptureService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateSermonCompanionDevotional implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public array $backoff = [30, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly int $planId, public readonly int $usageReservationId) {}

    public function uniqueId(): string
    {
        return (string) $this->planId;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAiSermonCompanionGenerator $generator, SermonCompanionScriptureService $scriptures, AiUsageService $usage): void
    {
        $plan = SermonCompanionDevotionalPlan::query()->with('companion')->findOrFail($this->planId);
        $plan->update(['status' => 'generating', 'processing_error' => null]);
        try {
            $verified = $scriptures->forCompanion($plan->companion);
            $generated = $generator->devotional($plan->companion, $verified, $plan->days_count);
            $days = $generated['days'] ?? [];
            if (count($days) !== $plan->days_count) {
                throw new \RuntimeException('OpenAI returned an incorrect devotional day count.');
            }
            DB::transaction(function () use ($plan, $verified, $days): void {
                $plan->days()->delete();
                foreach ($days as $index => $day) {
                    $source = $verified[$index % count($verified)];
                    $plan->days()->create(['day_number' => $index + 1, 'title' => $day['title'], 'theme' => $day['theme'], 'scripture_reference' => $source['reference'], 'translation' => $source['translation'], 'scripture_text' => $source['text'], 'reflection' => $day['reflection'], 'reflection_question' => $day['reflection_question'], 'practical_application' => $day['practical_application'], 'prayer' => $day['prayer']]);
                }
                $plan->update(['status' => 'completed', 'generated_at' => now()]);
                SermonCompanionProgress::updateOrCreate(['sermon_companion_devotional_plan_id' => $plan->id], ['user_id' => $plan->user_id, 'current_day' => 1, 'completed_days' => [], 'completion_percentage' => 0, 'last_activity_at' => now()]);
            });
            $usage->complete(AiUsageReservation::query()->findOrFail($this->usageReservationId), (array) ($generated['_usage'] ?? []));
        } catch (Throwable $exception) {
            $plan->update(['status' => 'failed', 'processing_error' => 'Devotional generation failed. Please retry.']);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        SermonCompanionDevotionalPlan::query()->whereKey($this->planId)->update(['status' => 'failed', 'processing_error' => 'Devotional generation failed. Please retry.']);
        $reservation = AiUsageReservation::query()->find($this->usageReservationId);
        if ($reservation !== null) {
            app(AiUsageService::class)->release($reservation);
        }
        Log::error('Sermon Companion devotional generation failed.', ['plan_id' => $this->planId, 'exception_class' => $exception ? $exception::class : null]);
    }
}
