<?php

namespace Tests\Unit\Rules;

use App\Services\SermonUrlService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SafeSermonUrlTest extends TestCase
{
    #[DataProvider('unsafeUrls')]
    public function test_rejects_private_and_local_network_urls(string $url): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new SermonUrlService)->normalize($url);
    }

    public function test_normalizes_public_url_and_removes_fragment(): void
    {
        $normalized = (new SermonUrlService)->normalize('https://8.8.8.8/sermons/hope?part=1#comments');

        $this->assertSame('https://8.8.8.8/sermons/hope?part=1', $normalized);
    }

    /** @return array<string, array{string}> */
    public static function unsafeUrls(): array
    {
        return [
            'loopback IPv4' => ['http://127.0.0.1/sermon'],
            'private IPv4' => ['http://192.168.1.20/sermon'],
            'localhost name' => ['http://localhost/sermon'],
            'credential URL' => ['https://user:secret@8.8.8.8/sermon'],
            'unsupported port' => ['https://8.8.8.8:8080/sermon'],
        ];
    }
}
