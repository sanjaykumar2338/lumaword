<?php

namespace App\Services;

use InvalidArgumentException;

class SermonUrlService
{
    public function normalize(string $url): string
    {
        $parts = parse_url(trim($url));

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException('Enter a complete sermon URL.');
        }

        $scheme = strtolower($parts['scheme']);
        $host = strtolower(rtrim($parts['host'], '.'));

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException('Only HTTP and HTTPS sermon URLs are supported.');
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            throw new InvalidArgumentException('URLs containing credentials are not supported.');
        }

        if (isset($parts['port']) && ! in_array($parts['port'], [80, 443], true)) {
            throw new InvalidArgumentException('The sermon URL uses an unsupported port.');
        }

        $this->assertPublicHost($host);

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return $scheme.'://'.$host.$port.$path.$query;
    }

    public function provider(string $url): string
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return match (true) {
            $host === 'youtu.be', str_ends_with($host, '.youtube.com'), $host === 'youtube.com',
            $host === 'youtube-nocookie.com', str_ends_with($host, '.youtube-nocookie.com') => 'youtube',
            $host === 'vimeo.com', str_ends_with($host, '.vimeo.com') => 'vimeo',
            $host === 'soundcloud.com', str_ends_with($host, '.soundcloud.com') => 'soundcloud',
            default => 'web',
        };
    }

    private function assertPublicHost(string $host): void
    {
        if ($host === '' || $host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local') || str_ends_with($host, '.internal')) {
            throw new InvalidArgumentException('Private or local network URLs are not allowed.');
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            $this->assertPublicIp($host);

            return;
        }

        $records = dns_get_record($host, DNS_A | DNS_AAAA);

        if ($records === false || $records === []) {
            throw new InvalidArgumentException('The sermon URL host could not be verified.');
        }

        foreach ($records as $record) {
            $address = $record['ip'] ?? $record['ipv6'] ?? null;

            if (is_string($address)) {
                $this->assertPublicIp($address);
            }
        }
    }

    private function assertPublicIp(string $address): void
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            throw new InvalidArgumentException('Private or reserved network URLs are not allowed.');
        }
    }
}
