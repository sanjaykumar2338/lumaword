<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BibleReaderControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_reader_endpoints_require_authentication(): void
    {
        $this->getJson('/api/v1/bible/translations')->assertUnauthorized();
        $this->getJson('/api/v1/bible/books')->assertUnauthorized();
        $this->getJson('/api/v1/bible/GEN/chapters/1')->assertUnauthorized();
        $this->getJson('/api/v1/bible/search?q=love')->assertUnauthorized();
    }

    public function test_reader_returns_all_public_domain_translations_and_books(): void
    {
        $user = User::factory()->create();

        $translations = $this->actingAs($user)->getJson('/api/v1/bible/translations');
        $translations->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.availability', 'free');

        foreach (['WEB', 'KJV', 'ASV'] as $translation) {
            $books = $this->actingAs($user)->getJson("/api/v1/bible/books?translation={$translation}");
            $books->assertOk()->assertJsonCount(66, 'data');
            $this->assertSame(1189, array_sum(array_column($books->json('data'), 'chapter_count')));
            $this->assertSame('Genesis', $books->json('data.0.name'));
            $this->assertSame('Revelation', $books->json('data.65.name'));
        }
    }

    public function test_reader_returns_complete_chapter_and_verse_data(): void
    {
        $user = User::factory()->create();

        $chapters = $this->actingAs($user)->getJson('/api/v1/bible/GEN/chapters?translation=KJV');
        $chapters->assertOk()->assertJsonPath('data.0.number', 1);

        $passage = $this->actingAs($user)->getJson('/api/v1/bible/GEN/chapters/1?translation=KJV&verse_start=1&verse_end=3');
        $passage->assertOk()
            ->assertJsonPath('data.translation.abbreviation', 'KJV')
            ->assertJsonPath('data.book.name', 'Genesis')
            ->assertJsonPath('data.verses.0.number', 1)
            ->assertJsonPath('data.verses.0.reference', 'Genesis 1:1');
        $this->assertCount(3, $passage->json('data.verses'));
    }

    public function test_reader_searches_words_and_references(): void
    {
        $user = User::factory()->create();

        $wordSearch = $this->actingAs($user)->getJson('/api/v1/bible/search?translation=ASV&q=In+the+beginning');
        $wordSearch->assertOk()->assertJsonPath('data.0.reference', 'Genesis 1:1');

        $referenceSearch = $this->actingAs($user)->getJson('/api/v1/bible/search?translation=WEB&q=John+3:16');
        $referenceSearch->assertOk()
            ->assertJsonPath('data.0.reference', 'John 3:16')
            ->assertJsonPath('data.0.translation.abbreviation', 'WEB');
    }

    public function test_reader_rejects_unknown_translation_and_book(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson('/api/v1/bible/books?translation=NIV')->assertUnprocessable();
        $this->actingAs($user)->getJson('/api/v1/bible/ZZZ/chapters/1')->assertNotFound();
    }
}
