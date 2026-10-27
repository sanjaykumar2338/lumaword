<?php

namespace Tests\Unit\Services;

use App\Services\ScriptureCatalog;
use InvalidArgumentException;
use Tests\TestCase;

class ScriptureCatalogTest extends TestCase
{
    public function test_returns_verified_web_passage_with_reference_and_translation(): void
    {
        $passage = (new ScriptureCatalog)->find('john-3-16-21');

        $this->assertSame('John 3:16-21', $passage['reference']);
        $this->assertSame('WEB', $passage['translation']);
        $this->assertStringStartsWith('16 For God so loved the world', $passage['text']);
    }

    public function test_unknown_passage_is_rejected_instead_of_inventing_text(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ScriptureCatalog)->find('not-a-real-passage');
    }

    public function test_options_include_book_chapter_and_verse_coordinates_for_selection(): void
    {
        $option = (new ScriptureCatalog)->options()->firstWhere('key', 'john-3-16-21');

        $this->assertSame('John', $option['book']);
        $this->assertSame(3, $option['chapter']);
        $this->assertSame(16, $option['verse_start']);
        $this->assertSame(21, $option['verse_end']);
    }
}
