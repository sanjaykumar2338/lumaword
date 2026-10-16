<?php

namespace App\Services;

use App\Contracts\SermonQuestionAnswerer;
use App\Models\Sermon;
use App\Models\SermonConversation;
use App\Models\SermonMessage;
use App\Models\User;
use App\SermonProcessingStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class AskSermonQuestion
{
    public function __construct(private readonly SermonQuestionAnswerer $answerer) {}

    /** @return array{conversation: SermonConversation, question: SermonMessage, answer: SermonMessage} */
    public function handle(User $user, Sermon $sermon, string $question, string $requestId): array
    {
        $unavailableReason = $this->unavailableReason($sermon);

        if ($unavailableReason !== null) {
            throw new HttpException(409, $unavailableReason);
        }

        $result = Cache::lock("sermon-qa:{$user->id}:{$sermon->id}", (int) config('sermons.qa.lock_seconds'))
            ->get(fn () => $this->answer($user, $sermon, $question, $requestId));

        if ($result === false) {
            throw new HttpException(409, 'Another question is already being answered. Please wait and try again.');
        }

        return $result;
    }

    public function unavailableReason(Sermon $sermon): ?string
    {
        if (blank($sermon->transcript)) {
            return 'Ask Luma is unavailable until this sermon has a transcript.';
        }

        if ($sermon->processing_status === SermonProcessingStatus::Failed) {
            return 'Ask Luma is unavailable because sermon processing failed. Retry processing first.';
        }

        if ($sermon->processing_status->isActive() || $sermon->processing_status === SermonProcessingStatus::Submitted) {
            return 'Ask Luma will be available after sermon processing finishes.';
        }

        return null;
    }

    /** @return array{conversation: SermonConversation, question: SermonMessage, answer: SermonMessage} */
    private function answer(User $user, Sermon $sermon, string $question, string $requestId): array
    {
        $conversation = SermonConversation::query()->firstOrCreate(
            ['user_id' => $user->id, 'sermon_id' => $sermon->id],
            ['title' => Str::limit($question, 120)],
        );
        $userMessage = $conversation->messages()->where('client_request_id', $requestId)->first();

        if ($userMessage !== null && $userMessage->content !== $question) {
            throw new HttpException(409, 'This request identifier was already used for a different question.');
        }

        if ($userMessage?->reply !== null) {
            $userMessage->update(['status' => SermonMessage::STATUS_COMPLETED]);

            return ['conversation' => $conversation, 'question' => $userMessage, 'answer' => $userMessage->reply];
        }

        $userMessage ??= $conversation->messages()->create([
            'role' => SermonMessage::ROLE_USER,
            'content' => $question,
            'status' => SermonMessage::STATUS_PENDING,
            'client_request_id' => $requestId,
        ]);
        $userMessage->update(['status' => SermonMessage::STATUS_PENDING]);
        $recentMessages = $conversation->messages()
            ->where('id', '<', $userMessage->id)
            ->where('status', SermonMessage::STATUS_COMPLETED)
            ->latest('id')
            ->limit((int) config('sermons.qa.recent_message_count'))
            ->get(['role', 'content'])
            ->reverse()
            ->values()
            ->map(fn (SermonMessage $message): array => ['role' => $message->role, 'content' => $message->content]);

        try {
            $result = $this->answerer->answer($sermon->loadMissing('notes'), $question, $recentMessages, $requestId);
        } catch (Throwable $exception) {
            $userMessage->update(['status' => SermonMessage::STATUS_FAILED]);
            report($exception);

            throw new HttpException(502, 'Luma could not answer that question right now. Please try again.');
        }

        $assistantMessage = DB::transaction(function () use ($conversation, $userMessage, $result): SermonMessage {
            $assistantMessage = $conversation->messages()->updateOrCreate(
                ['reply_to_message_id' => $userMessage->id],
                [
                    'role' => SermonMessage::ROLE_ASSISTANT,
                    'content' => $result['content'],
                    'sources' => $result['sources'],
                    'status' => SermonMessage::STATUS_COMPLETED,
                    'ai_provider' => 'openai',
                    'ai_model' => config('services.openai.model'),
                    'ai_response_id' => $result['response_id'],
                    'usage' => $result['usage'],
                ],
            );
            $userMessage->update(['status' => SermonMessage::STATUS_COMPLETED]);
            $conversation->touch();

            return $assistantMessage;
        });

        return ['conversation' => $conversation, 'question' => $userMessage, 'answer' => $assistantMessage];
    }
}
