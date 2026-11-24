<?php

namespace App\Enums;

enum SignupSource: string
{
    case FreeVideo = 'free_video';
    case Course = 'course';
    case Direct = 'direct';

    public function label(): string
    {
        return match ($this) {
            self::FreeVideo => 'Video gratis',
            self::Course => 'Halaman kursus',
            self::Direct => 'Langsung',
        };
    }
}
