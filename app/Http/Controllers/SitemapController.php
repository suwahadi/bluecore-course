<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Page;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        return response()->view('sitemap', [
            'staticUrls' => [route('home'), route('courses.index'), route('contact')],
            'courses' => Course::query()->published()->orderBy('id')->get(['slug', 'updated_at']),
            'pages' => Page::query()->published()->ordered()->get(['slug', 'updated_at']),
        ])->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
