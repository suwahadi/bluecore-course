<?php

namespace App\Models;

use App\Models\Concerns\HasSortOrder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'note', 'quote', 'sort_order', 'is_visible'])]
class Testimonial extends Model
{
    use HasFactory, HasSortOrder;

    protected $attributes = [
        'sort_order' => 1,
        'is_visible' => true,
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_visible' => 'boolean',
        ];
    }

    public function sortTieBreakerColumn(): string
    {
        return 'id';
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }
}
