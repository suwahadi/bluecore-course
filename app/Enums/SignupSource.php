<?php

namespace App\Enums;

enum SignupSource: string
{
    case FreeVideo = 'free_video';
    case Course = 'course';
    case Direct = 'direct';
}
