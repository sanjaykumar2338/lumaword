<?php

namespace Tests\Feature\Api\V1;

use App\Contracts\SermonQuestionAnswerer;
use App\Models\Sermon;
use App\Models\SermonConversation;
use App\Models\SermonMessage;
use App\Models\User;
use App\SermonProcessingStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class SermonMessageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_returns_401_when_user_is_unauthenticated(): void
    {
        $sermon = Sermon::factory()->create();

        $this->getJson("/api/v1/sermons/{$sermon->id}/conversation/messages")->assertUnauthorized();
    }

    public function test_owner_can_retrieve_an_empty_conversation(): void
    {
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->create();

        $this->actingAs($user)
            ->getJson("/api/v1/sermons/{$sermon->id}/conversation")
            ->assertOk()
            ->assertJsonPath('data', null)
            ->assertJsonPath('availability.available', true);

        $this->actingAs($user)
            ->getJson("/api/v1/sermons/{$sermon->id}/conversation/messages")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_message_history_is_paginated_and_chronological_within_each_page(): void
    {
        config(['sermons.qa.messages_per_page' => 2]);
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->create();
        $conversation = SermonConversation::factory()->for($user)->for($sermon)->create();
        $messages = SermonMessage::factory()->count(3)->for($conversation, 'conversation')->create();

        $response = $this->actingAs($user)
            ->getJson("/api/v1/sermons/{$sermon->id}/conversation/messages");

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $messages[1]->id)
            ->assertJsonPath('data.1.id', $messages[2]->id)
            ->assertJsonPath('meta.total', 3);
        $this->assertNotNull($response->json('links.next'));
    }

    public function test_user_cannot_access_another_users_conversation(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $sermon = Sermon::factory()->for($owner)->create();

        $this->actingAs($otherUser)
            ->getJson("/api/v1/sermons/{$sermon->id}/conversation/messages")
            ->assertForbidden();
    }

    public function test_valid_question_persists_conversation_and_grounded_answer(): void
    {
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->create();
        $this->mockSuccessfulAnswer();

        $response = $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/conversation/messages", [
            'question' => 'What did the pastor say about forgiveness?',
            'client_request_id' => '2f4563aa-84df-4b47-9e4d-44bf576193c2',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.answer.content', 'The pastor described forgiveness as an ongoing choice.')
            ->assertJsonPath('data.answer.sources.0.section', 'Transcript section 2');
        $this->assertDatabaseCount('sermon_conversations', 1);
        $this->assertDatabaseHas('sermon_messages', [
            'role' => SermonMessage::ROLE_USER,
            'status' => SermonMessage::STATUS_COMPLETED,
            'client_request_id' => '2f4563aa-84df-4b47-9e4d-44bf576193c2',
        ]);
        $this->assertDatabaseHas('sermon_messages', [
            'role' => SermonMessage::ROLE_ASSISTANT,
            'content' => 'The pastor described forgiveness as an ongoing choice.',
        ]);
        $this->assertDatabaseHas('daily_activities', [
            'user_id' => $user->id,
            'sermon_study' => true,
        ]);
    }

    public function test_follow_up_question_receives_recent_conversation_context(): void
    {
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->create();
        $conversation = SermonConversation::factory()->for($user)->for($sermon)->create();
        $firstQuestion = SermonMessage::factory()->for($conversation, 'conversation')->create([
            'content' => 'What did he say about forgiveness?',
        ]);
        SermonMessage::factory()->assistant()->for($conversation, 'conversation')->create([
            'reply_to_message_id' => $firstQuestion->id,
            'content' => 'He called it an ongoing choice.',
        ]);
        $this->mock(SermonQuestionAnswerer::class, function (MockInterface $mock): void {
            $mock->shouldReceive('answer')->once()->withArgs(
                fn (Sermon $sermon, string $question, Collection $history): bool => $question === 'How can I apply that this week?'
                    && $history->pluck('content')->all() === [
                        'What did he say about forgiveness?',
                        'He called it an ongoing choice.',
                    ],
            )->andReturn($this->answerResult());
        });

        $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/conversation/messages", [
            'question' => 'How can I apply that this week?',
            'client_request_id' => '032c7659-c214-4c1a-ac94-88f9b856416e',
        ])->assertCreated();
    }

    public function test_api_failure_is_safe_and_same_request_can_be_retried_without_duplicates(): void
    {
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->create();
        $requestId = 'ed3110d3-ad65-41dd-b85a-3857d96be1f3';
        $this->mock(SermonQuestionAnswerer::class, function (MockInterface $mock): void {
            $mock->shouldReceive('answer')->once()->andThrow(new RuntimeException('Secret upstream details'));
        });

        $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/conversation/messages", [
            'question' => 'What is the main point?',
            'client_request_id' => $requestId,
        ])->assertStatus(502)->assertDontSee('Secret upstream details');
        $this->assertDatabaseHas('sermon_messages', [
            'client_request_id' => $requestId,
            'status' => SermonMessage::STATUS_FAILED,
        ]);

        $this->mockSuccessfulAnswer();
        $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/conversation/messages", [
            'question' => 'What is the main point?',
            'client_request_id' => $requestId,
        ])->assertCreated();
        $this->assertDatabaseCount('sermon_conversations', 1);
        $this->assertDatabaseCount('sermon_messages', 2);
    }

    public function test_duplicate_completed_request_returns_the_existing_answer(): void
    {
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->create();
        $payload = [
            'question' => 'What is the main point?',
            'client_request_id' => '9eb03dd4-fc45-45fc-a4a4-dfd6cc6e7151',
        ];
        $this->mockSuccessfulAnswer();

        $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/conversation/messages", $payload)->assertCreated();
        $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/conversation/messages", $payload)->assertCreated();

        $this->assertDatabaseCount('sermon_messages', 2);
    }

    public function test_question_validation_rejects_empty_and_oversized_input(): void
    {
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->create();

        $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/conversation/messages", [
            'question' => '   ',
            'client_request_id' => '87fa13f8-2f55-42c3-be79-797c2d666093',
        ])->assertUnprocessable()->assertJsonValidationErrors('question');

        $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/conversation/messages", [
            'question' => str_repeat('x', (int) config('sermons.qa.maximum_question_characters') + 1),
            'client_request_id' => '2b328088-547b-443f-8a02-a7238ac20122',
        ])->assertUnprocessable()->assertJsonValidationErrors('question');
        $this->assertDatabaseCount('sermon_messages', 0);
    }

    public function test_questions_are_unavailable_without_a_transcript_or_during_processing_or_after_failure(): void
    {
        $user = User::factory()->create();
        $payload = [
            'question' => 'What is the main point?',
            'client_request_id' => '47d8dd1e-bf75-48ef-a302-1a742456167f',
        ];
        $withoutTranscript = Sermon::factory()->for($user)->create(['transcript' => null]);
        $processing = Sermon::factory()->for($user)->create(['processing_status' => SermonProcessingStatus::Analyzing]);
        $failed = Sermon::factory()->for($user)->failed()->create();

        $this->actingAs($user)->postJson("/api/v1/sermons/{$withoutTranscript->id}/conversation/messages", $payload)
            ->assertConflict()->assertJsonFragment(['message' => 'Ask Luma is unavailable until this sermon has a transcript.']);
        $this->actingAs($user)->postJson("/api/v1/sermons/{$processing->id}/conversation/messages", [...$payload, 'client_request_id' => '2adae6b2-054b-4668-aa56-f0d843a34121'])
            ->assertConflict()->assertJsonFragment(['message' => 'Ask Luma will be available after sermon processing finishes.']);
        $this->actingAs($user)->postJson("/api/v1/sermons/{$failed->id}/conversation/messages", [...$payload, 'client_request_id' => '55827a87-5348-4a54-b2f0-a30ca7e80ed7'])
            ->assertConflict()->assertJsonFragment(['message' => 'Ask Luma is unavailable because sermon processing failed. Retry processing first.']);
    }

    public function test_daily_question_limit_returns_429(): void
    {
        config(['sermons.qa.daily_questions_per_user' => 2]);
        RateLimiter::clear('');
        $user = User::factory()->create();
        $sermon = Sermon::factory()->for($user)->create();
        $this->mock(SermonQuestionAnswerer::class, function (MockInterface $mock): void {
            $mock->shouldReceive('answer')->twice()->andReturn($this->answerResult());
        });

        foreach (['94bd6c8c-5da5-4ef0-983c-66c9d6787804', '92678d07-b6c8-4ac0-bc7e-e210087c29ee'] as $requestId) {
            $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/conversation/messages", [
                'question' => 'What is the main point?',
                'client_request_id' => $requestId,
            ])->assertCreated();
        }

        $this->actingAs($user)->postJson("/api/v1/sermons/{$sermon->id}/conversation/messages", [
            'question' => 'What should I discuss?',
            'client_request_id' => '93fe6e7b-24fd-4122-bb80-a02feb9354c2',
        ])->assertTooManyRequests();
    }

    private function mockSuccessfulAnswer(): void
    {
        $this->mock(SermonQuestionAnswerer::class, function (MockInterface $mock): void {
            $mock->shouldReceive('answer')->once()->andReturn($this->answerResult());
        });
    }

    /** @return array<string, mixed> */
    private function answerResult(): array
    {
        return [
            'content' => 'The pastor described forgiveness as an ongoing choice.',
            'sources' => [['section' => 'Transcript section 2', 'excerpt' => 'Forgiveness is an ongoing choice.']],
            'response_id' => 'resp_qa_123',
            'usage' => ['input_tokens' => 100, 'output_tokens' => 30],
        ];
    }
}
