<?php

namespace App\View\Components;

use App\Models\Page;
use App\Models\User;
use App\Support\Format;
use App\Support\FreeVideo;
use App\Support\Settings;
use App\Support\SiteLogo;
use Illuminate\View\Component;
use Illuminate\View\View;

class SiteHeader extends Component
{
    public string $siteName;

    public ?string $slogan;

    public string $logoUrl;

    public ?User $user;

    public ?string $freeVideoUrl;

    public array $links;

    public array $accountLinks;

    public bool $onAccountPage;

    public function __construct()
    {
        $this->siteName = (string) Settings::get('site_name');
        $this->slogan = Settings::get('site_slogan');
        $this->logoUrl = SiteLogo::url();
        $this->user = auth()->user();
        $this->freeVideoUrl = $this->user ? null : FreeVideo::url();

        $links = [['path' => '/courses', 'label' => 'Kursus']];
        $about = Page::query()->published()->where('system_key', 'about')->first(['title', 'slug']);

        if ($about) {
            $links[] = ['path' => '/'.$about->slug, 'label' => $about->title];
        }

        if ($this->user) {
            $links[] = ['path' => '/my-courses', 'label' => 'Kursus saya'];
        }

        if ($this->user?->isAdmin()) {
            $links[] = ['path' => '/admin', 'label' => 'Admin'];
        }

        $this->links = array_map(fn (array $link) => $link + ['current' => $this->isCurrent($link['path'])], $links);

        $this->accountLinks = array_map(fn (array $link) => $link + ['current' => $this->isCurrent($link['path'])], [
            ['path' => '/orders', 'label' => 'Pesanan', 'icon' => 'receipt'],
            ['path' => '/account', 'label' => 'Akun', 'icon' => 'user'],
        ]);

        $this->onAccountPage = request()->is('orders', 'orders/*', 'order/*', 'account', 'account/*');
    }

    public function initials(): string
    {
        return Format::initials($this->user?->name ?? '');
    }

    public function render(): View
    {
        return view('components.site-header');
    }

    private function isCurrent(string $path): bool
    {
        $segment = ltrim($path, '/');

        return request()->is($segment, $segment.'/*');
    }
}
