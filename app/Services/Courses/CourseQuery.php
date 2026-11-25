<?php

namespace App\Services\Courses;

use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Services\Progress\ProgressService;
use App\Support\Settings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class CourseQuery
{
    public function __construct(private ProgressService $progress)
    {
    }

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

    public function catalog(string $term, ?Category $category, string $sort): Builder
    {
        $query = $this->cards()
            ->when($term !== '', fn (Builder $query) => $query->where('title', 'like', '%'.addcslashes($term, '%_\\').'%'))
            ->when($category, fn (Builder $query) => $query->where('category_id', $category->id));

        return match ($sort) {
            'cheapest' => $query->orderBy('price')->latest('published_at'),
            'priciest' => $query->orderByDesc('price')->latest('published_at'),
            'popular' => $query->withCount('enrollments')->orderByDesc('enrollments_count')->latest('published_at'),
            default => $query->latest('published_at')->latest('id'),
        };
    }

    public function ownedIds(?User $user): array
    {
        return $user ? Enrollment::query()->where('user_id', $user->id)->pluck('course_id')->all() : [];
    }

    public function ownedProgress(?User $user, iterable $courses): array
    {
        if ($user === null) {
            return [];
        }

        $shown = collect($courses)->pluck('id')->all();

        return $this->progress->summaries($user, array_values(array_intersect($shown, $this->ownedIds($user))));
    }
}
