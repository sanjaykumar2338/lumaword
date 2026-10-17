<?php

namespace Tests\Unit\Services;

use App\Services\YoutubeCaptionParser;
use Tests\TestCase;

class YoutubeCaptionParserTest extends TestCase
{
    public function test_vtt_metadata_timestamps_tags_and_duplicate_cues_are_removed(): void
    {
        $vtt = <<<'VTT'
WEBVTT
Kind: captions
Language: en

00:00:00.000 --> 00:00:02.000
Welcome &amp; <c.green>grace</c>

cue-2
00:00:02.000 --> 00:00:04.000
Welcome &amp; <c.green>grace</c>

00:00:04.000 --> 00:00:06.000
God is faithful.
VTT;

        $this->assertSame('Welcome & grace God is faithful.', (new YoutubeCaptionParser)->parseVtt($vtt));
    }
}
