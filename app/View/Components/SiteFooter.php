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

    public array $links;

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
        $this->links = $this->navigationLinks(Cache::rememberForever(Page::FOOTER_CACHE_KEY, fn () => Page::query()
            ->published()
            ->inFooter()
            ->ordered()
            ->get(['title', 'slug', 'system_key'])
            ->map(fn (Page $page) => ['title' => $page->title, 'url' => '/'.$page->slug, 'key' => $page->system_key])
            ->all()));
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

    private function navigationLinks(array $pages): array
    {
        $pages = collect($pages);
        $pinned = fn (string $key) => $pages->firstWhere('key', $key);

        return collect([
            $pinned('about'),
            ['title' => 'Kursus', 'url' => '/courses'],
            $pinned('faq'),
            ['title' => 'Hubungi Kami', 'url' => '/contact'],
            ...$pages->whereNotIn('key', ['about', 'faq'])->all(),
        ])->filter()->values()->all();
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
