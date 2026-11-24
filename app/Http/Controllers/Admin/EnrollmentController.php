<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EnrollmentRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\AccessGrantedNotification;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;

class EnrollmentController extends Controller
{
    public function store(EnrollmentRequest $request, User $user): RedirectResponse
    {
        $course = Course::query()->findOrFail($request->validated('course_id'));
        $alreadyEnrolled = fn () => back()->withInput()->withErrors(['course_id' => $user->name.' sudah punya akses ke kursus ini.'], 'grant-'.$user->id);

        if (Enrollment::query()->where(['user_id' => $user->id, 'course_id' => $course->id])->exists()) {
            return $alreadyEnrolled();
        }

        try {
            Enrollment::query()->create([
                'user_id' => $user->id,
                'course_id' => $course->id,
                'order_id' => null,
                'granted_by_user_id' => $request->user()->id,
                'note' => $request->validated('note'),
            ]);
        } catch (UniqueConstraintViolationException) {
            return $alreadyEnrolled();
        }

        $user->notify(new AccessGrantedNotification($course));

        return back()->with('status', 'Akses '.$course->title.' diberikan ke '.$user->name.'.');
    }
}
