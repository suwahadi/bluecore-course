<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileName
{
    private const DOWNLOAD_NAME_LIMIT = 150;

    public static function random(string $directory, string $extension, string $disk): string
    {
        do {
            $path = trim($directory, '/').'/'.Str::lower(Str::random(14)).'.'.$extension;
        } while (Storage::disk($disk)->exists($path));

        return $path;
    }

    public static function forDownload(?string $original, string $fallback = 'materi.pdf'): string
    {
        $name = basename(str_replace(chr(92), '/', (string) $original));
        $name = preg_replace('/[\x00-\x1F\x7F"*:?<>|]+/u', '', $name) ?? '';
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '', ' .');

        if ($name === '') {
            return $fallback;
        }

        $stem = preg_replace('/\.pdf$/i', '', $name);
        $extension = $stem === $name ? '.pdf' : substr($name, -4);

        return rtrim(mb_substr($stem, 0, self::DOWNLOAD_NAME_LIMIT - 4), ' .').$extension;
    }
}
