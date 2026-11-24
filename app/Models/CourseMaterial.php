<?php

namespace App\Models;

use App\Models\Concerns\HasSortOrder;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course_id', 'title', 'file_path', 'original_name', 'file_size', 'sort_order'])]
class CourseMaterial extends Model
{
    use HasFactory, HasSortOrder;

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function sortTieBreakerColumn(): string
    {
        return 'id';
    }

    public function sortSiblings(): Builder
    {
        return static::query()->where('course_id', $this->course_id);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
