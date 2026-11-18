<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\HasSortOrder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'slug', 'content', 'is_published', 'show_in_footer', 'sort_order', 'system_key'])]
class Page extends Model
{
    use HasFactory, HasSlug, HasSortOrder;

    public const RESERVED_SLUGS = [
        'account', 'admin', 'api', 'assets', 'build', 'checkout', 'contact', 'course', 'courses',
        'demo', 'email', 'forgot-password', 'free-video', 'learn', 'livewire', 'login', 'logout',
        'my-courses', 'order', 'orders', 'payments', 'register', 'reset-password', 'storage', 'up',
        'user', 'verify-email',
    ];

    protected $attributes = [
        'is_published' => true,
        'show_in_footer' => true,
        'sort_order' => 1,
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_in_footer' => 'boolean',
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

    public static function systemPage(string $key): ?self
    {
        return static::query()->where('system_key', $key)->first();
    }
}
