<?php

namespace App\Support;

class NextPath
{
    private const MAX_LENGTH = 2000;

    public static function sanitize(?string $value): ?string
    {
        if ($value === null || $value === '' || strlen($value) > self::MAX_LENGTH) {
            return null;
        }

        if (! str_starts_with($value, '/') || str_starts_with($value, '//') || str_contains($value, '\\')) {
            return null;
        }

        if (preg_match('/[\x00-\x20\x7F]/', $value)) {
            return null;
        }

        return $value;
    }
}
