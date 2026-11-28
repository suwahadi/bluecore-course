<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Models\Testimonial;
use App\Services\Courses\CourseQuery;
use App\Support\FreeVideo;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public const LATEST_LIMIT = 8;

    public function __invoke(Request $request, CourseQuery $courses): View
    {
        $latest = $courses->latest(self::LATEST_LIMIT);
        $faq = Page::query()->published()->where('system_key', 'faq')->first();
        $featured = $courses->featured();
        $checkoutPath = $featured ? '/courses/'.$featured->slug.'/checkout' : null;

        return view('home', [
            'featured' => $featured,
            'courseUrl' => $featured ? route('courses.show', $featured) : url('/courses'),
            'buyUrl' => match (true) {
                $checkoutPath === null => null,
                $request->user() !== null => url($checkoutPath),
                default => url('/register?next='.urlencode($checkoutPath)),
            },
            'latest' => $latest,
            'owned' => $courses->ownedProgress($request->user(), $latest),
            'testimonials' => Testimonial::query()->visible()->ordered()->get(),
            'freeVideoUrl' => FreeVideo::url(),
            'faq' => $faq,
            'faqItems' => $faq?->faqItems() ?? [],
        ]);
    }
}
