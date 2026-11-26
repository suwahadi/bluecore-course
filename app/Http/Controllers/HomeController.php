<?php

namespace App\Http\Controllers;

use App\Models\Testimonial;
use App\Services\Courses\CourseQuery;
use App\Support\Settings;
use App\Support\YouTubeUrl;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public const LATEST_LIMIT = 8;

    public function __invoke(Request $request, CourseQuery $courses): View
    {
        $latest = $courses->latest(self::LATEST_LIMIT);

        return view('home', [
            'featured' => $courses->featured(),
            'latest' => $latest,
            'owned' => $courses->ownedProgress($request->user(), $latest),
            'testimonials' => Testimonial::query()->visible()->ordered()->get(),
            'freeVideo' => YouTubeUrl::parse(Settings::get('lead_magnet_youtube_url')) !== null,
        ]);
    }
}
