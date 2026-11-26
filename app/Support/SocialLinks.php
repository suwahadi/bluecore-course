<?php

namespace App\Support;

class SocialLinks
{
    private const NETWORKS = [
        ['icon' => 'instagram', 'setting' => 'instagram_url', 'label' => 'Instagram'],
        ['icon' => 'youtube', 'setting' => 'youtube_url', 'label' => 'YouTube'],
        ['icon' => 'tiktok', 'setting' => 'tiktok_url', 'label' => 'TikTok'],
    ];

    public static function rows(): array
    {
        $rows = [];

        foreach (self::NETWORKS as $social) {
            $url = Settings::get($social['setting']);

            if ($url) {
                $rows[] = ['icon' => $social['icon'], 'label' => $social['label'], 'text' => self::handle($url), 'url' => $url];
            }
        }

        return $rows;
    }

    private static function handle(string $url): string
    {
        $segment = basename(rtrim((string) (parse_url($url, PHP_URL_PATH) ?: $url), '/'));

        return str_starts_with($segment, '@') ? $segment : '@'.$segment;
    }
}
