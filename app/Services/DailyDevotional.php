<?php

namespace App\Services;

use Carbon\CarbonInterface;

class DailyDevotional
{
    public function __construct(private readonly ScriptureCatalog $scriptures) {}

    /** @return array<string, mixed> */
    public function forDate(CarbonInterface $date): array
    {
        $entries = $this->entries();
        $entry = $entries[($date->dayOfYear - 1) % count($entries)];
        $scripture = $this->scriptures->find($entry['scripture_key']);

        return [
            'id' => null,
            'title' => $entry['title'],
            'scripture_key' => $entry['scripture_key'],
            'scripture_reference' => $scripture['reference'],
            'translation' => $scripture['translation'],
            'scripture_text' => $scripture['text'],
            'reflection' => $entry['reflection'],
            'application' => $entry['application'],
            'prayer' => $entry['prayer'],
            'source_type' => 'daily',
            'is_ai_generated' => false,
            'generated_at' => null,
        ];
    }

    /** @return array<int, array{title: string, scripture_key: string, reflection: string, application: string, prayer: string}> */
    private function entries(): array
    {
        return [
            [
                'title' => 'Known and Shepherded',
                'scripture_key' => 'psalm-23',
                'reflection' => 'Psalm 23 does not promise an easy path. It names dark valleys and enemies, yet keeps returning to the Shepherd’s presence. Our confidence rests less in knowing the road ahead than in knowing who walks with us.',
                'application' => 'Name one uncertainty you are carrying. Pause before trying to solve it and entrust it to God in a short, honest prayer.',
                'prayer' => 'Lord, shepherd me through what I can see and what I cannot. Restore my soul and teach me to follow your faithful leading today. Amen.',
            ],
            [
                'title' => 'Grace That Makes Us New',
                'scripture_key' => 'ephesians-2-1-10',
                'reflection' => 'Grace is not a reward for spiritual performance. God makes us alive in Christ and then prepares good work for us to walk in. We serve from acceptance, not for acceptance.',
                'application' => 'Receive God’s grace without bargaining. Then choose one small good work you can do today as a grateful response.',
                'prayer' => 'God of mercy, thank you for making me alive in Christ. Free me from boasting and lead me into the good you have prepared. Amen.',
            ],
            [
                'title' => 'Seek First',
                'scripture_key' => 'matthew-6-25-34',
                'reflection' => 'Jesus meets anxiety with an invitation to notice God’s care and reorder our attention. Seeking the Kingdom first does not deny today’s needs; it places them in the hands of a Father who already knows them.',
                'application' => 'Write down tomorrow’s worry. Beside it, write one faithful action that belongs to today, and give the rest to God.',
                'prayer' => 'Father, you know what I need. Quiet my anxious striving and help me seek your Kingdom in the work of this day. Amen.',
            ],
            [
                'title' => 'Remain in Christ',
                'scripture_key' => 'john-15-1-8',
                'reflection' => 'Fruitfulness begins with remaining. Jesus does not ask branches to manufacture life; he calls them to stay connected to him. Prayer, Scripture, and obedience keep us open to the life only Christ supplies.',
                'application' => 'Set aside ten unhurried minutes to reread this passage and sit with one phrase that draws you toward Jesus.',
                'prayer' => 'Jesus, true vine, keep me close to you. Prune what hinders love and grow fruit that brings glory to the Father. Amen.',
            ],
            [
                'title' => 'A Practiced Peace',
                'scripture_key' => 'philippians-4-4-9',
                'reflection' => 'Paul joins prayer, gratitude, disciplined attention, and faithful practice. God’s peace is a gift, and this passage also gives us habits that make room to receive and live in that peace.',
                'application' => 'Turn one request into a prayer with thanksgiving, then deliberately focus on something true, honorable, and worthy of praise.',
                'prayer' => 'God of peace, guard my heart and mind in Christ. Train my attention toward what is true and help me practice what I know. Amen.',
            ],
            [
                'title' => 'Faith That Acts',
                'scripture_key' => 'james-1-19-27',
                'reflection' => 'God’s word is not a mirror for a passing glance. James calls us to receive it humbly, let it expose us, and carry it into speech, mercy, and daily conduct.',
                'application' => 'Choose one instruction in this passage—listen, slow your anger, guard your speech, or care for someone vulnerable—and act on it today.',
                'prayer' => 'God, plant your word deeply in me. Make me quick to listen and ready to obey, so my faith becomes visible in love. Amen.',
            ],
            [
                'title' => 'Nothing Can Separate Us',
                'scripture_key' => 'romans-8-31-39',
                'reflection' => 'Paul does not minimize suffering. He places every threat beside the love of God revealed in Christ and declares that none of them can sever that bond.',
                'application' => 'Read verses 38–39 aloud. Name the circumstance that makes you feel far from God and answer it with the passage’s promise.',
                'prayer' => 'God, anchor me in your inseparable love. In pressure and uncertainty, remind me that Christ intercedes and holds me fast. Amen.',
            ],
        ];
    }
}
