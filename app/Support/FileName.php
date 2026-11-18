<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileName
{
    public static function random(string $directory, string $extension, string $disk): string
    {
        do {
            $path = trim($directory, '/').'/'.Str::lower(Str::random(14)).'.'.$extension;
        } while (Storage::disk($disk)->exists($path));

        return $path;
    }
}
