<?php

namespace App\Services\Courses;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CourseQuery
{
    public function cards(): Builder
    {
        return Course::query()
            ->published()
            ->with('category')
            ->withCount('segments')
            ->withSum('segments', 'duration_seconds');
    }

    public function latest(int $limit): Collection
    {
        return $this->cards()->latest('published_at')->latest('id')->limit($limit)->get();
    }

    public function featured(): ?Course
    {
        $chosen = Settings::get('featured_course_id');
        $query = fn () => $this->cards()->with('segments');

        return ($chosen ? $query()->whereKey($chosen)->first() : null)
            ?? $query()->latest('published_at')->latest('id')->first();
    }

    public function ownedIds(?User $user): array
    {
        return $user ? Enrollment::query()->where('user_id', $user->id)->pluck('course_id')->all() : [];
    }
}
