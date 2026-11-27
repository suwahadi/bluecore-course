<?php

namespace App\View\Components;

use App\Models\Page;
use App\Support\Settings;
use App\Support\SocialLinks;
use App\Support\WhatsApp;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;
use Illuminate\View\View;

class SiteFooter extends Component
{
    private const MARKETPLACES = [
        ['name' => 'Shopee', 'image' => 'shopee', 'setting' => 'shopee_url', 'width' => 85, 'height' => 96],
        ['name' => 'Tokopedia', 'image' => 'tokopedia', 'setting' => 'tokopedia_url', 'width' => 69, 'height' => 72],
        ['name' => 'TikTok Shop', 'image' => 'tiktok-shop', 'setting' => 'tiktokshop_url', 'width' => 82, 'height' => 96],
    ];

    public string $siteName;

    public ?string $slogan;

    public ?string $description;

    public ?string $disclaimer;

    public string $owner;

    public array $pages;

    public array $contacts;

    public array $socials;

    public array $markets;

    public function __construct()
    {
        $this->siteName = (string) Settings::get('site_name');
        $this->slogan = Settings::get('site_slogan');
        $this->description = Settings::get('site_description');
        $this->disclaimer = Settings::get('footer_disclaimer');
        $this->owner = Settings::get('company_name') ?: $this->siteName;
        $this->pages = Cache::rememberForever(Page::FOOTER_CACHE_KEY, fn () => Page::query()
            ->published()
            ->inFooter()
            ->ordered()
            ->get(['title', 'slug'])
            ->map(fn (Page $page) => ['title' => $page->title, 'slug' => $page->slug])
            ->all());
        $this->contacts = $this->contactRows();
        $this->socials = SocialLinks::rows();
        $this->markets = array_values(array_filter(
            array_map(fn (array $market) => $market + ['url' => Settings::get($market['setting'])], self::MARKETPLACES),
            fn (array $market) => filled($market['url']),
        ));
    }

    public function render(): View
    {
        return view('components.site-footer');
    }

    private function contactRows(): array
    {
        $rows = [];
        $whatsapp = Settings::get('whatsapp');
        $email = Settings::get('email');

        if ($whatsapp) {
            $rows[] = [
                'icon' => 'whatsapp',
                'label' => 'WhatsApp',
                'text' => WhatsApp::display($whatsapp),
                'url' => WhatsApp::url($whatsapp),
            ];
        }

        if ($email) {
            $rows[] = ['icon' => 'mail', 'label' => 'Email', 'text' => $email, 'url' => 'mailto:'.$email];
        }

        return $rows;
    }
}
