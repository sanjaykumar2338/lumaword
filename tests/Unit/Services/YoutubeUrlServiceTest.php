<?php

namespace Tests\Unit\Services;

use App\Services\YoutubeUrlService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class YoutubeUrlServiceTest extends TestCase
{
    #[DataProvider('supportedLinks')]
    public function test_supported_video_links_return_the_video_id(string $url): void
    {
        $this->assertSame('dQw4w9WgXcQ', (new YoutubeUrlService)->videoId($url));
    }

    /** @return array<string, array{string}> */
    public static function supportedLinks(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10'],
            'short' => ['https://youtu.be/dQw4w9WgXcQ'],
            'shorts' => ['https://youtube.com/shorts/dQw4w9WgXcQ'],
            'live' => ['https://m.youtube.com/live/dQw4w9WgXcQ'],
            'privacy embed' => ['https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ'],
        ];
    }

    #[DataProvider('unsupportedLinks')]
    public function test_non_video_youtube_links_are_rejected(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new YoutubeUrlService)->videoId($url);
    }

    /** @return array<string, array{string}> */
    public static function unsupportedLinks(): array
    {
        return [
            'channel' => ['https://www.youtube.com/@church'],
            'playlist' => ['https://www.youtube.com/playlist?list=PL123'],
            'missing id' => ['https://www.youtube.com/watch'],
            'lookalike host' => ['https://youtube.com.example.test/watch?v=dQw4w9WgXcQ'],
            'malformed id' => ['https://youtu.be/not-valid'],
        ];
    }
}
