<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use App\Support\Markdown;
use App\Support\YouTubeUrl;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[Fillable(['title', 'slug', 'content', 'youtube_url', 'requires_signup', 'upsell_content', 'is_published', 'show_in_footer', 'sort_order', 'system_key'])]
class Page extends Model
{
    use HasFactory, HasSlug, HasSortOrder;

    public const FOOTER_CACHE_KEY = 'footer_pages';

    public const RESERVED_SLUGS = [
        'account', 'admin', 'api', 'assets', 'build', 'checkout', 'contact', 'course', 'courses',
        'demo', 'email', 'fonts', 'forgot-password', 'images', 'learn', 'livewire', 'login', 'logout',
        'my-courses', 'order', 'orders', 'payments', 'register', 'reset-password', 'storage', 'up',
        'user', 'verify-email',
    ];

    protected $attributes = [
        'is_published' => true,
        'show_in_footer' => true,
        'requires_signup' => true,
        'sort_order' => 1,
    ];

    protected static function booted(): void
    {
        static::saved(fn () => static::forgetFooterAfterCommit());
        static::deleted(fn () => static::forgetFooterAfterCommit());
    }

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_in_footer' => 'boolean',
            'requires_signup' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function slugSourceColumn(): string
    {
        return 'title';
    }

    public function sortTieBreakerColumn(): string
    {
        return 'title';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeInFooter(Builder $query): Builder
    {
        return $query->where('show_in_footer', true);
    }

    public function scopeLanding(Builder $query): Builder
    {
        return $query->whereNotNull('youtube_url');
    }

    public function videoId(): ?string
    {
        return YouTubeUrl::parse($this->youtube_url);
    }

    public function faqIntro(): ?string
    {
        return Markdown::html(trim($this->faqParts()[0]));
    }

    public function faqItems(): array
    {
        $parts = $this->faqParts();
        $items = [];

        for ($index = 1; $index < count($parts); $index += 2) {
            $items[] = ['question' => trim($parts[$index]), 'answer' => Markdown::html(trim($parts[$index + 1])) ?? ''];
        }

        return $items;
    }

    private function faqParts(): array
    {
        return preg_split('/^###[ \t]+(.+?)[ \t#]*$/m', (string) $this->content, -1, PREG_SPLIT_DELIM_CAPTURE);
    }

    private static function forgetFooterAfterCommit(): void
    {
        DB::afterCommit(fn () => Cache::forget(self::FOOTER_CACHE_KEY));
    }

    public static function slugRoutePattern(): string
    {
        $reserved = implode('|', array_map(fn (string $slug) => preg_quote($slug, '#'), self::RESERVED_SLUGS));

        return '(?!(?:'.$reserved.')$)(?!livewire)[a-z0-9]+(?:-[a-z0-9]+)*';
    }

    public static function systemPage(string $key): ?self
    {
        return static::query()->where('system_key', $key)->first();
    }
}
