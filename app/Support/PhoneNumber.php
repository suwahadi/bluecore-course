<?php

namespace App\Support;

class PhoneNumber
{
    public const PATTERN = '/^(\+?62|0)8[0-9]{7,12}$/';

    public static function clean(?string $value): string
    {
        return str_replace([' ', '-'], '', trim((string) $value));
    }

    public static function isValid(?string $value): bool
    {
        return (bool) preg_match(self::PATTERN, self::clean($value));
    }
}
