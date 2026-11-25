<?php

namespace App\Http\Controllers;

use App\Http\Requests\PositionRequest;
use App\Models\CourseSegment;
use App\Services\Progress\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class ProgressController extends Controller
{
    public function __construct(private ProgressService $progress)
    {
    }

    public function store(Request $request, CourseSegment $segment): JsonResponse|RedirectResponse
    {
        $course = $segment->course;
        Gate::authorize('learn', $course);

        $this->progress->markCompleted($request->user(), $segment);

        $ids = $course->segments()->pluck('id');
        $nextId = $ids->get($ids->search($segment->id) + 1);
        $nextUrl = $nextId === null ? null : route('learn.show', [$course, $nextId]);

        if ($request->expectsJson()) {
            return response()->json(['next_url' => $nextUrl]);
        }

        return redirect()->to($nextUrl ?? route('learn.show', [$course, $segment]));
    }

    public function position(PositionRequest $request, CourseSegment $segment): Response
    {
        $this->progress->recordPosition($request->user(), $segment, (int) $request->validated('position'), (int) $request->validated('duration'));

        return response()->noContent();
    }
}
