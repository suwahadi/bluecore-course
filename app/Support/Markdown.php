<?php

namespace App\Support;

use Illuminate\Support\Str;

class Markdown
{
    public static function html(?string $text): ?string
    {
        return filled($text)
            ? Str::markdown($text, ['html_input' => 'strip', 'allow_unsafe_links' => false])
            : null;
    }
}
