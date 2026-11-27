<?php

namespace App\Support;

use App\Models\Page;

class FreeVideo
{
    public static function url(): ?string
    {
        $page = Page::query()->published()->landing()->find(Settings::get('free_video_page_id'));

        return $page?->videoId() ? url('/'.$page->slug) : null;
    }
}
