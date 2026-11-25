<?php

namespace App\Http\Controllers;

use App\Services\Courses\CourseQuery;
use App\Support\Format;
use App\Support\Settings;
use App\Support\YouTubeUrl;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FreeVideoController extends Controller
{
    public const MORE_COURSES = 4;

    public function __invoke(Request $request, CourseQuery $courses): View
    {
        $videoId = YouTubeUrl::parse(Settings::get('lead_magnet_youtube_url'));

        if ($videoId === null) {
            return view('free-video.empty');
        }

        $user = $request->user();
        $moreCourses = $courses->latest(self::MORE_COURSES);

        return view('free-video.show', [
            'title' => Settings::get('lead_magnet_title') ?: 'Video gratis',
            'paragraphs' => Format::paragraphs(Settings::get('lead_magnet_description')),
            'videoId' => $user ? $videoId : null,
            'courses' => $moreCourses,
            'owned' => $courses->ownedProgress($user, $moreCourses),
            'activeForm' => old('_form') === 'login' ? 'login' : 'register',
        ]);
    }
}
