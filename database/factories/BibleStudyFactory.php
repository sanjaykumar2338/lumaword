<?php

namespace Database\Factories;

use App\BibleStudyDepth;
use App\Models\BibleStudy;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BibleStudy>
 */
class BibleStudyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $scripture = config('scripture.passages.psalm-23');

        return [
            'user_id' => User::factory(),
            'title' => 'The Shepherd Who Stays Near',
            'scripture_key' => 'psalm-23',
            'scripture_reference' => $scripture['reference'],
            'translation' => $scripture['translation'],
            'scripture_text' => $scripture['text'],
            'depth' => BibleStudyDepth::GoDeeper,
            'content' => self::content(),
            'personal_notes' => null,
            'ai_provider' => 'openai',
            'ai_model' => 'test-model',
            'ai_response_id' => 'resp_test',
            'prompt_version' => 'bible-study-v1',
            'request_hash' => hash('sha256', fake()->uuid()),
            'generated_at' => now(),
        ];
    }

    /** @return array<string, mixed> */
    public static function content(): array
    {
        return [
            'title' => 'The Shepherd Who Stays Near',
            'overview' => 'Psalm 23 presents God as a faithful shepherd whose presence sustains his people.',
            'passage_context' => 'This psalm moves from provision and guidance to danger, welcome, and enduring fellowship.',
            'book_introduction' => [
                'authorship' => 'The Psalter preserves prayers and songs from several authors and periods; Psalm 23 is attributed to David.',
                'audience' => 'Israel used these songs in personal and gathered worship.',
                'purpose' => 'The psalm gives language for trusting God in every circumstance.',
                'major_themes' => ['God as shepherd', 'God\'s presence'],
                'biblical_narrative' => 'The shepherd image develops across Scripture as a picture of divine care and faithful leadership.',
                'scholarly_uncertainties' => ['The precise setting in David\'s life is not stated.'],
            ],
            'historical_context' => [[
                'detail' => 'Shepherds guided, protected, and provided for vulnerable flocks.',
                'certainty' => 'established',
            ]],
            'word_studies' => [[
                'term' => 'shepherd',
                'language' => 'Hebrew',
                'transliteration' => 'ra‘ah',
                'pronunciation' => 'rah-AH',
                'contextual_meaning' => 'to tend and care for a flock',
                'possible_translations' => ['shepherd', 'tend'],
                'translation_significance' => 'The image describes active provision and guidance, not merely a title.',
                'caution' => 'The word\'s meaning here comes from its sentence and literary setting, not from an isolated root meaning.',
            ]],
            'book_outline' => [[
                'heading' => 'Book I',
                'range' => 'Psalms 1–41',
                'summary' => 'Prayers and songs centered on trust, lament, and kingship.',
                'contains_passage' => true,
            ]],
            'literary_context' => [
                'genre' => 'Poetry and song of trust',
                'structure' => ['The shepherd image in verses 1–4', 'The host image in verses 5–6'],
                'observations' => ['The direct address to God begins as the poem enters the darkest valley.'],
            ],
            'themes' => [[
                'theme' => 'God\'s presence',
                'explanation' => 'Confidence rests on God being with the speaker even in danger.',
            ]],
            'cross_references' => [[
                'reference' => 'John 10:11',
                'connection' => 'Jesus uses the good-shepherd image to describe his self-giving care.',
            ]],
            'study_questions' => ['Where does the psalm locate its confidence when circumstances become threatening?'],
            'application' => [
                'practical_reflections' => ['Name one place where you need to practice trust rather than deny difficulty.'],
                'prayer_prompts' => ['Ask for awareness of God\'s presence in a difficult place.'],
                'journaling_questions' => ['Which image in the psalm most clearly speaks to your present season?'],
                'group_discussion_topics' => ['How can communities embody shepherd-like care without claiming God\'s role?'],
            ],
        ];
    }
}
