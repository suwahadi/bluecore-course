<?php

namespace App\View\Components;

use App\Support\Settings;
use App\Support\SiteLogo;
use Illuminate\View\Component;
use Illuminate\View\View;

class SeoMeta extends Component
{
    public string $fullTitle;

    public string $metaDescription;

    public string $imageUrl;

    public string $canonical;

    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public ?string $image = null,
        public bool $noindex = false,
    ) {
        $siteName = (string) Settings::get('site_name');

        $this->fullTitle = $title ? $title.': '.$siteName : $siteName;
        $this->metaDescription = $description ?: (string) Settings::get('site_description');
        $this->imageUrl = $image ?: SiteLogo::url();
        $this->canonical = url()->current();
    }

    public function render(): View
    {
        return view('components.seo-meta');
    }
}
