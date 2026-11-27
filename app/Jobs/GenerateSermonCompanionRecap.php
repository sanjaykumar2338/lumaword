<?php

namespace App\Jobs;

use App\Models\AiUsageReservation;
use App\Models\SermonCompanion;
use App\Services\AiUsageService;
use App\Services\OpenAiSermonCompanionGenerator;
use App\Services\SermonCompanionScriptureService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateSermonCompanionRecap implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public array $backoff = [30, 120, 300];

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly int $companionId, public readonly int $usageReservationId) {}

    public function uniqueId(): string
    {
        return (string) $this->companionId;
    }

    /**
     * Execute the job.
     */
    public function handle(OpenAiSermonCompanionGenerator $generator, SermonCompanionScriptureService $scriptures, AiUsageService $usage): void
    {
        $companion = SermonCompanion::query()->findOrFail($this->companionId);
        $companion->update(['status' => 'generating_recap', 'processing_error' => null]);
        try {
            $verified = $scriptures->forCompanion($companion);
            $recap = $generator->recap($companion, $verified);
            $cards = $generator->memoryCards($companion, $verified);
            if (count($cards) !== count($verified)) {
                throw new \RuntimeException('OpenAI returned an incomplete Scripture memory card set.');
            }
            DB::transaction(function () use ($companion, $recap, $cards, $verified): void {
                $companion->update(['recap' => collect($recap)->except(['_usage', '_response_id'])->all(), 'status' => 'completed', 'completed_at' => now()]);
                foreach ($cards as $card) {
                    $source = collect($verified)->firstWhere('reference', $card['reference']);
                    if ($source === null) {
                        throw new \RuntimeException('OpenAI returned an unverified Scripture memory card reference.');
                    }
                    $blanked = $source['text'];
                    $answers = [];
                    foreach (array_slice(array_filter($card['blank_words'] ?? [], 'is_string'), 0, 8) as $word) {
                        $updated = preg_replace('/'.preg_quote($word, '/').'/u', '____', $blanked, 1, $count);
                        if (is_string($updated) && $count === 1) {
                            $blanked = $updated;
                            $answers[] = $word;
                        }
                    }
                    if ($answers !== []) {
                        $companion->memoryCards()->updateOrCreate(['reference' => $source['reference'], 'translation' => $source['translation']], ['verse_text' => $source['text'], 'fill_in_blank' => ['text' => $blanked], 'correct_answers' => $answers]);
                    }
                }
            });
            $usage->complete(AiUsageReservation::query()->findOrFail($this->usageReservationId), (array) ($recap['_usage'] ?? []));
        } catch (Throwable $exception) {
            $companion->update(['status' => 'failed', 'processing_error' => 'Recap generation failed. Please retry.']);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        SermonCompanion::query()->whereKey($this->companionId)->update(['status' => 'failed', 'processing_error' => 'Recap generation failed. Please retry.']);
        $reservation = AiUsageReservation::query()->find($this->usageReservationId);
        if ($reservation !== null) {
            app(AiUsageService::class)->release($reservation);
        }
        Log::error('Sermon Companion recap generation failed.', ['companion_id' => $this->companionId, 'exception_class' => $exception ? $exception::class : null]);
    }
}
