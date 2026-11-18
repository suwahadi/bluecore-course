<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'course_segment_id', 'last_position_seconds', 'percent', 'completed_at'])]
class SegmentProgress extends Model
{
    use HasFactory;

    protected $table = 'segment_progress';

    protected $attributes = [
        'last_position_seconds' => 0,
        'percent' => 0,
    ];

    protected function casts(): array
    {
        return [
            'last_position_seconds' => 'integer',
            'percent' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function segment(): BelongsTo
    {
        return $this->belongsTo(CourseSegment::class, 'course_segment_id');
    }
}
