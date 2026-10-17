<?php

namespace App\Services;

use InvalidArgumentException;

class YoutubeUrlService
{
    public function videoId(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['host'])) {
            throw new InvalidArgumentException('Enter a supported YouTube video link.');
        }

        $host = strtolower(rtrim($parts['host'], '.'));
        $path = trim($parts['path'] ?? '', '/');
        $videoId = null;

        if ($host === 'youtu.be') {
            $videoId = explode('/', $path)[0] ?? null;
        } elseif (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com', 'music.youtube.com'], true)) {
            if ($path === 'watch') {
                parse_str($parts['query'] ?? '', $query);
                $videoId = is_string($query['v'] ?? null) ? $query['v'] : null;
            } elseif (preg_match('/^(?:shorts|live|embed)\/([^\/]+)/', $path, $matches) === 1) {
                $videoId = $matches[1];
            }
        } elseif (in_array($host, ['youtube-nocookie.com', 'www.youtube-nocookie.com'], true)
            && preg_match('/^embed\/([^\/]+)/', $path, $matches) === 1) {
            $videoId = $matches[1];
        }

        if (! is_string($videoId) || preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) !== 1) {
            throw new InvalidArgumentException('Enter a supported YouTube video link, not a channel, playlist, or search page.');
        }

        return $videoId;
    }
}
