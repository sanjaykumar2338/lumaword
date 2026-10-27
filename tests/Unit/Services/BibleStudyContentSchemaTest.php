<?php

namespace Tests\Unit\Services;

use App\Services\BibleStudyContentSchema;
use Database\Factories\BibleStudyFactory;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BibleStudyContentSchemaTest extends TestCase
{
    public function test_accepts_complete_structured_study(): void
    {
        $content = BibleStudyFactory::content();

        $validated = (new BibleStudyContentSchema)->validate($content);

        $this->assertSame($content['title'], $validated['title']);
        $this->assertSame($content['book_introduction']['authorship'], $validated['book_introduction']['authorship']);
        $this->assertSame($content['historical_context'], $validated['historical_context']);
        $this->assertSame($content['application'], $validated['application']);
    }

    public function test_rejects_unsupported_certainty_label(): void
    {
        $content = BibleStudyFactory::content();
        $content['historical_context'][0]['certainty'] = 'certainly';
        $this->expectException(ValidationException::class);

        (new BibleStudyContentSchema)->validate($content);
    }

    public function test_rejects_outline_that_does_not_locate_current_passage(): void
    {
        $content = BibleStudyFactory::content();
        $content['book_outline'][0]['contains_passage'] = false;
        $this->expectException(ValidationException::class);

        (new BibleStudyContentSchema)->validate($content);
    }
}
