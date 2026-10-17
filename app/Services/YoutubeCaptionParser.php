<?php

namespace App\Services;

class YoutubeCaptionParser
{
    public function parseVtt(string $contents): string
    {
        $lines = preg_split('/\R/u', preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents) ?: [];
        $captions = [];
        $inNote = false;

        foreach ($lines as $index => $line) {
            $line = trim($line);

            if ($line === '') {
                $inNote = false;

                continue;
            }

            if ($line === 'WEBVTT' || str_starts_with($line, 'Kind:') || str_starts_with($line, 'Language:')) {
                continue;
            }

            if (str_starts_with($line, 'NOTE')) {
                $inNote = true;

                continue;
            }

            if ($inNote || str_contains($line, '-->') || preg_match('/^(STYLE|REGION)$/', $line) === 1) {
                continue;
            }

            $nextLine = trim($lines[$index + 1] ?? '');

            if (str_contains($nextLine, '-->')) {
                continue;
            }

            $plainText = html_entity_decode(strip_tags($line), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $plainText = trim((string) preg_replace('/\s+/u', ' ', $plainText));

            if ($plainText !== '' && end($captions) !== $plainText) {
                $captions[] = $plainText;
            }
        }

        return trim(implode(' ', $captions));
    }
}
