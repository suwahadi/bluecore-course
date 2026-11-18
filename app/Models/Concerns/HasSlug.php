<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasSlug
{
    abstract public function slugSourceColumn(): string;

    protected static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (blank($model->slug)) {
                $model->slug = $model->uniqueSlugFrom((string) $model->{$model->slugSourceColumn()});
            }
        });
    }

    public function uniqueSlugFrom(string $source): string
    {
        $base = Str::slug($source) ?: Str::lower(Str::random(8));
        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
