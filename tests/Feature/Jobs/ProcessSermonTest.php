<?php

namespace Tests\Feature\Jobs;

use App\Contracts\MediaTranscriber;
use App\Contracts\SermonAnalyzer;
use App\Jobs\ProcessSermon;
use App\Models\Sermon;
use App\SermonProcessingStatus;
use App\SermonSourceType;
use App\TranscriptSource;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class ProcessSermonTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_transcript_sermon_persists_notes_and_completes(): void
    {
        $sermon = Sermon::factory()->create(['processing_status' => SermonProcessingStatus::Queued]);
        $analyzer = $this->mock(SermonAnalyzer::class);
        $analyzer->shouldReceive('analyze')->once()->andReturn($this->analysisResult());
        $transcriber = $this->mock(MediaTranscriber::class);
        $transcriber->shouldNotReceive('transcribe');

        (new ProcessSermon($sermon->id))->handle($analyzer, $transcriber);

        $sermon->refresh();
        $this->assertSame(SermonProcessingStatus::Completed, $sermon->processing_status);
        $this->assertNotNull($sermon->processed_at);
        $this->assertDatabaseHas('sermon_notes', ['sermon_id' => $sermon->id, 'ai_model' => config('services.openai.model')]);
    }

    public function test_upload_is_transcribed_privately_deleted_and_analyzed(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('sermons/1/media/message.mp3', 'audio');
        $sermon = Sermon::factory()->create([
            'source_type' => SermonSourceType::Upload,
            'processing_status' => SermonProcessingStatus::Queued,
            'transcript' => null,
            'transcript_source' => TranscriptSource::None,
            'media_path' => 'sermons/1/media/message.mp3',
            'media_original_name' => 'message.mp3',
            'media_mime' => 'audio/mpeg',
        ]);
        $transcript = str_repeat('The sermon calls listeners to trust God. ', 8);
        $transcriber = $this->mock(MediaTranscriber::class);
        $transcriber->shouldReceive('transcribe')->once()->andReturn([
            'text' => $transcript,
            'language' => 'en',
            'duration_seconds' => 600,
        ]);
        $analyzer = $this->mock(SermonAnalyzer::class);
        $analyzer->shouldReceive('analyze')->once()->andReturn($this->analysisResult());

        (new ProcessSermon($sermon->id))->handle($analyzer, $transcriber);

        $sermon->refresh();
        $this->assertSame($transcript, $sermon->transcript);
        $this->assertSame(TranscriptSource::Upload, $sermon->transcript_source);
        $this->assertNull($sermon->media_path);
        Storage::disk('local')->assertMissing('sermons/1/media/message.mp3');
    }

    public function test_terminal_failure_records_safe_retry_message(): void
    {
        $sermon = Sermon::factory()->create(['processing_status' => SermonProcessingStatus::Analyzing]);

        (new ProcessSermon($sermon->id))->failed(new RuntimeException('private transcript details'));

        $sermon->refresh();
        $this->assertSame(SermonProcessingStatus::Failed, $sermon->processing_status);
        $this->assertSame('Processing could not be completed. You can safely retry.', $sermon->processing_error);
        $this->assertStringNotContainsString('private transcript details', $sermon->processing_error);
    }

    public function test_job_has_bounded_retry_and_backoff_configuration(): void
    {
        $job = new ProcessSermon(123);

        $this->assertSame(3, $job->tries);
        $this->assertSame([30, 120, 300], $job->backoff);
        $this->assertSame((int) config('sermons.processing.timeout_seconds'), $job->timeout);
        $this->assertSame((string) $job->sermonId, $job->uniqueId());
    }

    /** @return array{notes: array<string, mixed>, response_id: string, usage: array<string, int>} */
    private function analysisResult(): array
    {
        return [
            'notes' => [
                'title' => 'Trusting God',
                'main_idea' => 'God is trustworthy.',
                'summary' => 'The sermon calls listeners to trust God.',
                'outline' => [['heading' => 'Trust', 'details' => 'Trust God in daily life.']],
                'key_points' => ['God remains faithful.'],
                'scripture_references' => [],
                'themes' => ['trust'],
                'applications' => ['Pray about one worry.'],
                'discussion_questions' => ['Where is trust difficult?'],
            ],
            'response_id' => 'resp_test',
            'usage' => ['input_tokens' => 100, 'output_tokens' => 50],
        ];
    }
}
