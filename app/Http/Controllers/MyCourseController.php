<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Services\Progress\ProgressService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MyCourseController extends Controller
{
    public function __construct(private ProgressService $progress)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $enrollments = Enrollment::query()
            ->where('user_id', $user->id)
            ->with('course.category')
            ->orderByRaw('last_accessed_at is null')
            ->orderByDesc('last_accessed_at')
            ->latest('id')
            ->get();

        return view('my-courses.index', [
            'enrollments' => $enrollments,
            'progress' => $this->progress->summaries($user, $enrollments->pluck('course_id')->all()),
        ]);
    }
}
