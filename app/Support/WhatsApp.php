<?php

namespace App\Support;

class WhatsApp
{
    public static function url(string $number, ?string $text = null): string
    {
        return 'https://wa.me/'.$number.'?text='.rawurlencode($text ?? self::greeting().', saya ingin bertanya.');
    }

    public static function greeting(): string
    {
        return 'Halo '.Settings::get('site_name');
    }

    public static function display(string $number): string
    {
        return '0'.substr($number, 2);
    }
}
