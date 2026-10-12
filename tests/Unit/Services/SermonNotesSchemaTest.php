<?php

namespace Tests\Unit\Services;

use App\Services\SermonNotesSchema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SermonNotesSchemaTest extends TestCase
{
    public function test_accepts_complete_structured_notes(): void
    {
        $notes = $this->validNotes();

        $validated = (new SermonNotesSchema)->validate($notes);

        $this->assertSame($notes, $validated);
    }

    public function test_rejects_malformed_scripture_reference(): void
    {
        $notes = $this->validNotes();
        unset($notes['scripture_references'][0]['verification_status']);

        $this->expectException(ValidationException::class);

        (new SermonNotesSchema)->validate($notes);
    }

    /** @return array<string, mixed> */
    private function validNotes(): array
    {
        return [
            'title' => 'Faithful Prayer',
            'main_idea' => 'Jesus calls his people to persistent prayer.',
            'summary' => 'The sermon explains prayer as trusting dependence on God.',
            'outline' => [['heading' => 'Ask', 'details' => 'Bring needs honestly to God.']],
            'key_points' => ['Prayer is an act of trust.'],
            'scripture_references' => [[
                'reference' => 'Luke 11:1-13',
                'explicitly_mentioned' => true,
                'verification_status' => 'verified',
                'context' => 'The speaker explains Jesus teaching on prayer.',
                'transcript_excerpt' => 'Lord, teach us to pray.',
            ]],
            'themes' => ['prayer'],
            'applications' => ['Set aside time to pray this week.'],
            'discussion_questions' => ['What keeps you from persistent prayer?'],
        ];
    }
}
