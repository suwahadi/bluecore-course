<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class Format
{
    public static function rupiah(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    public static function datetime(CarbonInterface $date): string
    {
        return $date->copy()->timezone('Asia/Jakarta')->locale('id')->isoFormat('dddd, D MMM YYYY HH:mm');
    }

    public static function duration(int $seconds): string
    {
        $minutes = (int) ceil(max($seconds, 0) / 60);
        $hours = intdiv($minutes, 60);
        $rest = $minutes % 60;

        if ($hours === 0) {
            return $rest.' menit';
        }

        return $rest === 0 ? $hours.' jam' : $hours.' jam '.$rest.' menit';
    }

    public static function paragraphs(?string $text): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/\R\s*\R/', (string) $text) ?: []),
            fn (string $paragraph) => $paragraph !== '',
        ));
    }

    public static function fileSize(int $bytes): string
    {
        if ($bytes < 1024 * 1024) {
            return max(1, (int) round($bytes / 1024)).' KB';
        }

        return number_format($bytes / 1024 / 1024, 1, ',', '.').' MB';
    }

    public static function initials(string $name): string
    {
        return Str::of($name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $word) => Str::upper(Str::substr($word, 0, 1)))
            ->implode('');
    }
}
