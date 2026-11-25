<?php

namespace App\Support;

class MailText
{
    private const MARKDOWN_CHARACTERS = '\\`*_{}[]()#+-.!|<>~';

    public static function plain(?string $text): string
    {
        return addcslashes((string) $text, self::MARKDOWN_CHARACTERS);
    }
}
