<?php

namespace App\Support;

class YouTubeUrl
{
    private const HOSTS = [
        'youtube.com',
        'www.youtube.com',
        'm.youtube.com',
        'youtu.be',
        'www.youtube-nocookie.com',
    ];

    private const PATH_PREFIXES = ['shorts', 'embed', 'live'];

    public static function parse(?string $url): ?string
    {
        $parts = parse_url(trim((string) $url));

        if (! is_array($parts) || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower($parts['host'] ?? '');

        if (! in_array($host, self::HOSTS, true)) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', $parts['path'] ?? '')));
        parse_str($parts['query'] ?? '', $query);

        $candidate = match (true) {
            $host === 'youtu.be' => $segments[0] ?? null,
            isset($segments[0]) && in_array($segments[0], self::PATH_PREFIXES, true) => $segments[1] ?? null,
            default => is_string($query['v'] ?? null) ? $query['v'] : null,
        };

        return is_string($candidate) && preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) ? $candidate : null;
    }
}
