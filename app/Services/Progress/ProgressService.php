<?php

namespace App\Services\Progress;

use App\Models\Course;
use App\Models\CourseSegment;
use App\Models\Enrollment;
use App\Models\SegmentProgress;
use App\Models\User;
use App\Support\CourseProgress;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProgressService
{
    public const COMPLETE_AT_PERCENT = 90;

    public const TOUCH_EVERY_MINUTES = 5;

    public function markCompleted(User $user, CourseSegment $segment): void
    {
        SegmentProgress::query()->upsert(
            [['user_id' => $user->id, 'course_segment_id' => $segment->id, 'percent' => 100, 'completed_at' => now()]],
            ['user_id', 'course_segment_id'],
            ['percent', 'completed_at'],
        );
    }

    public function recordPosition(User $user, CourseSegment $segment, int $position, int $duration): void
    {
        $duration = max($duration, 1);
        $position = min(max($position, 0), $duration);
        $keys = ['user_id' => $user->id, 'course_segment_id' => $segment->id];

        SegmentProgress::query()->fillAndInsertOrIgnore([$keys]);

        DB::transaction(function () use ($keys, $position, $duration) {
            $progress = SegmentProgress::query()->where($keys)->lockForUpdate()->sole();
            $percent = min(100, max($progress->percent, intdiv($position * 100, $duration)));

            $progress->forceFill([
                'last_position_seconds' => $position,
                'percent' => $percent,
                'completed_at' => $progress->completed_at ?? ($percent >= self::COMPLETE_AT_PERCENT ? now() : null),
            ])->save();
        });
    }

    public function forSegments(User $user, Collection $segments): Collection
    {
        return SegmentProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('course_segment_id', $segments->pluck('id'))
            ->get(['course_segment_id', 'last_position_seconds', 'percent', 'completed_at'])
            ->keyBy('course_segment_id');
    }

    public function fromSegments(Collection $segments, Collection $progressBySegment): CourseProgress
    {
        return new CourseProgress($segments->map(fn (CourseSegment $segment) => $progressBySegment->get($segment->id)?->completed_at !== null)->values()->all());
    }

    public function summaries(User $user, array $courseIds): array
    {
        if ($courseIds === []) {
            return [];
        }

        $segments = CourseSegment::query()
            ->whereIn('course_id', $courseIds)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'course_id']);
        $done = SegmentProgress::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->whereIn('course_segment_id', $segments->pluck('id'))
            ->pluck('course_segment_id')
            ->flip();
        $byCourse = $segments->groupBy('course_id');

        $summaries = [];

        foreach ($courseIds as $courseId) {
            $summaries[$courseId] = new CourseProgress(
                $byCourse->get($courseId, collect())->map(fn (CourseSegment $segment) => $done->has($segment->id))->values()->all(),
            );
        }

        return $summaries;
    }

    public function touch(User $user, Course $course): void
    {
        Enrollment::query()
            ->where(['user_id' => $user->id, 'course_id' => $course->id])
            ->where(fn (Builder $query) => $query
                ->whereNull('last_accessed_at')
                ->orWhere('last_accessed_at', '<', now()->subMinutes(self::TOUCH_EVERY_MINUTES)))
            ->update(['last_accessed_at' => now()]);
    }
}
