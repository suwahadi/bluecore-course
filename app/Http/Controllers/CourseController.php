<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\Order;
use App\Services\Progress\ProgressService;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(private ProgressService $progress)
    {
    }

    public function index(): View
    {
        return view('courses.index');
    }

    public function show(Request $request, Course $course): View
    {
        abort_unless(Gate::allows('view', $course), 404);

        $course->load(['category', 'segments', 'materials'])
            ->loadCount(['segments', 'materials', 'enrollments'])
            ->loadSum('segments', 'duration_seconds');

        $user = $request->user();
        $canLearn = $user?->can('learn', $course) ?? false;

        return view('courses.show', [
            'course' => $course,
            'canLearn' => $canLearn,
            'adminFee' => (int) Settings::get('admin_fee'),
            'progress' => $canLearn ? $this->progress->fromSegments($course->segments, $this->progress->forSegments($user, $course->segments)) : null,
            'openOrder' => $user && ! $canLearn ? Order::query()
                ->where(['user_id' => $user->id, 'course_id' => $course->id, 'status' => OrderStatus::Pending])
                ->where('expires_at', '>', now())
                ->latest()
                ->first() : null,
        ]);
    }
}
