<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    public function show(Page $page): View
    {
        abort_unless(Gate::allows('view', $page), 404);

        return view('pages.show', [
            'page' => $page,
            'html' => filled($page->content)
                ? Str::markdown($page->content, ['html_input' => 'strip', 'allow_unsafe_links' => false])
                : null,
        ]);
    }
}
