<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class SiteLogo
{
    public static function url(): string
    {
        $logo = Settings::get('logo');

        return $logo ? Storage::disk('public')->url($logo) : asset('images/logo.webp');
    }
}
