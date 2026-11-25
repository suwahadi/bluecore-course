<?php

namespace App\Policies;

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CoursePolicy
{
    public function view(?User $user, Course $course): bool
    {
        return $course->status === CourseStatus::Published || (bool) $user?->isAdmin();
    }

    public function learn(User $user, Course $course): Response
    {
        $allowed = $user->isAdmin() || Enrollment::query()->where(['user_id' => $user->id, 'course_id' => $course->id])->exists();

        return $allowed ? Response::allow() : Response::deny('Anda belum punya akses ke kursus ini.');
    }
}
