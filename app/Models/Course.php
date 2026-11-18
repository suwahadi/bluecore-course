<?php

namespace App\Models;

use App\Enums\CourseStatus;
use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id', 'title', 'slug', 'short_description', 'description', 'thumbnail_path',
    'price', 'compare_at_price', 'status', 'published_at',
])]
class Course extends Model
{
    use HasFactory, HasSlug;

    protected $attributes = [
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'compare_at_price' => 'integer',
            'status' => CourseStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function slugSourceColumn(): string
    {
        return 'title';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function segments(): HasMany
    {
        return $this->hasMany(CourseSegment::class)->orderBy('sort_order')->orderBy('id');
    }

    public function materials(): HasMany
    {
        return $this->hasMany(CourseMaterial::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', CourseStatus::Published);
    }
}
