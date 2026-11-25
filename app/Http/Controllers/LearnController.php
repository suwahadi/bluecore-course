<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSegment;
use App\Services\Progress\ProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class LearnController extends Controller
{
    private const RESUME_AFTER_SECONDS = 5;

    private const RESUME_UNTIL_END_SECONDS = 10;

    public function __construct(private ProgressService $progress)
    {
    }

    public function index(Request $request, Course $course): View|RedirectResponse
    {
        Gate::authorize('learn', $course);

        $segments = $course->segments()->get(['id', 'course_id', 'sort_order']);

        if ($segments->isEmpty()) {
            return view('learn.empty', ['course' => $course]);
        }

        $progress = $this->progress->forSegments($request->user(), $segments);
        $target = $segments->first(fn (CourseSegment $segment) => $progress->get($segment->id)?->completed_at === null) ?? $segments->first();

        return redirect()->route('learn.show', [$course, $target]);
    }

    public function show(Request $request, Course $course, CourseSegment $segment): View
    {
        Gate::authorize('learn', $course);

        $user = $request->user();
        $this->progress->touch($user, $course);
        $course->load(['segments', 'materials']);
        $segments = $course->segments;
        $progress = $this->progress->forSegments($user, $segments);
        $index = $segments->search(fn (CourseSegment $item) => $item->id === $segment->id);

        return view('learn.show', [
            'course' => $course,
            'segment' => $segment,
            'segments' => $segments,
            'progress' => $progress,
            'courseProgress' => $this->progress->fromSegments($segments, $progress),
            'index' => $index,
            'previous' => $index > 0 ? $segments->get($index - 1) : null,
            'next' => $segments->get($index + 1),
            'resumeAt' => $this->resumePosition((int) $progress->get($segment->id)?->last_position_seconds, $segment->duration_seconds),
        ]);
    }

    private function resumePosition(int $position, ?int $duration): int
    {
        $tooEarly = $position <= self::RESUME_AFTER_SECONDS;
        $tooLate = $duration !== null && $position >= $duration - self::RESUME_UNTIL_END_SECONDS;

        return $tooEarly || $tooLate ? 0 : $position;
    }
}
