<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\Courses\CourseQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    public const MORE_COURSES = 4;

    public function show(Request $request, Page $page, CourseQuery $courses): View
    {
        abort_unless(Gate::allows('view', $page), 404);

        $html = filled($page->content)
            ? Str::markdown($page->content, ['html_input' => 'strip', 'allow_unsafe_links' => false])
            : null;
        $videoId = $page->videoId();

        if ($videoId === null) {
            return view('pages.show', ['page' => $page, 'html' => $html]);
        }

        $user = $request->user();
        $moreCourses = $courses->latest(self::MORE_COURSES);

        return view('pages.landing', [
            'page' => $page,
            'html' => $html,
            'videoId' => $user || ! $page->requires_signup ? $videoId : null,
            'courses' => $moreCourses,
            'owned' => $courses->ownedProgress($user, $moreCourses),
            'activeForm' => old('_form') === 'login' ? 'login' : 'register',
        ]);
    }
}
