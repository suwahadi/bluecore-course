<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Services\Courses\CourseQuery;
use App\Support\Markdown;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(Request $request, Page $page, CourseQuery $courses): View
    {
        abort_unless(Gate::allows('view', $page), 404);

        $html = Markdown::html($page->content);
        $videoId = $page->videoId();

        if ($videoId === null) {
            $faqItems = $page->system_key === 'faq' ? $page->faqItems() : [];

            return view('pages.show', [
                'page' => $page,
                'html' => $html,
                'faqItems' => $faqItems,
                'faqIntro' => $faqItems ? $page->faqIntro() : null,
            ]);
        }

        $user = $request->user();
        $upsellCourse = $courses->featured();

        return view('pages.landing', [
            'page' => $page,
            'html' => $html,
            'videoId' => $user || ! $page->requires_signup ? $videoId : null,
            'upsellCourse' => $upsellCourse,
            'upsellHtml' => Markdown::html($page->upsell_content),
            'ownsUpsell' => $upsellCourse && $user?->can('learn', $upsellCourse),
            'activeForm' => old('_form') === 'login' ? 'login' : 'register',
        ]);
    }
}
