<?php

namespace App\View\Components;

use App\Models\Page;
use App\Support\Settings;
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

    private const SOCIALS = [
        ['icon' => 'instagram', 'setting' => 'instagram_url', 'label' => 'Instagram'],
        ['icon' => 'youtube', 'setting' => 'youtube_url', 'label' => 'YouTube'],
        ['icon' => 'tiktok', 'setting' => 'tiktok_url', 'label' => 'TikTok'],
    ];

    public string $siteName;

    public ?string $description;

    public array $pages;

    public array $contacts;

    public array $socials;

    public array $markets;

    public function __construct()
    {
        $this->siteName = (string) Settings::get('site_name');
        $this->description = Settings::get('site_description');
        $this->pages = Cache::rememberForever(Page::FOOTER_CACHE_KEY, fn () => Page::query()
            ->published()
            ->inFooter()
            ->ordered()
            ->get(['title', 'slug'])
            ->map(fn (Page $page) => ['title' => $page->title, 'slug' => $page->slug])
            ->all());
        $this->contacts = $this->contactRows();
        $this->socials = $this->socialRows();
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
                'text' => '0'.substr($whatsapp, 2),
                'url' => 'https://wa.me/'.$whatsapp,
            ];
        }

        if ($email) {
            $rows[] = ['icon' => 'mail', 'label' => 'Email', 'text' => $email, 'url' => 'mailto:'.$email];
        }

        return $rows;
    }

    private function socialRows(): array
    {
        $rows = [];

        foreach (self::SOCIALS as $social) {
            $url = Settings::get($social['setting']);

            if ($url) {
                $rows[] = ['icon' => $social['icon'], 'label' => $social['label'], 'text' => $this->handle($url), 'url' => $url];
            }
        }

        return $rows;
    }

    private function handle(string $url): string
    {
        $segment = basename(rtrim((string) (parse_url($url, PHP_URL_PATH) ?: $url), '/'));

        return str_starts_with($segment, '@') ? $segment : '@'.$segment;
    }
}
