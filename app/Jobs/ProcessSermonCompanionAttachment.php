<?php

namespace App\Jobs;

use App\Models\SermonCompanionAttachment;
use App\SermonCompanionAttachmentProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessSermonCompanionAttachment implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(public readonly int $attachmentId)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(SermonCompanionAttachmentProcessor $processor): void
    {
        $attachment = SermonCompanionAttachment::query()->findOrFail($this->attachmentId);
        $attachment->update(['status' => 'processing', 'processing_error' => null]);
        try {
            $result = $processor->process($attachment);
            $attachment->update([...$result, 'status' => 'awaiting_review', 'processed_at' => now()]);
            $attachment->companion()->update(['status' => 'awaiting_review']);
        } catch (\Throwable $exception) {
            $attachment->update(['status' => 'failed', 'processing_error' => 'Attachment processing failed. Please retry.']);
            $attachment->companion()->update(['status' => 'failed', 'processing_error' => 'Attachment processing failed. Please retry.']);
            throw $exception;
        }
    }
}
