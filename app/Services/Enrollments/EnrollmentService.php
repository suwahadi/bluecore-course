<?php

namespace App\Services\Enrollments;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;

class EnrollmentService
{
    public function grant(User $user, Course $course, ?Order $order = null): Enrollment
    {
        return Enrollment::query()->firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['order_id' => $order?->id],
        );
    }
}
