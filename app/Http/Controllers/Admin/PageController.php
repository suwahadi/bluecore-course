<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PageRequest;
use App\Http\Requests\Admin\SortOrderRequest;
use App\Models\Page;
use App\Services\Ordering\SortOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(private SortOrderService $sorter)
    {
    }

    public function index(): View
    {
        return view('admin.pages.index', [
            'pages' => Page::query()->ordered()->paginate(20),
            'nextPosition' => Page::query()->count() + 1,
        ]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $this->save(new Page, $request->validated());

        return back()->with('status', 'Halaman dibuat.');
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $this->save($page, $request->validated());

        return back()->with('status', 'Halaman disimpan.');
    }

    public function move(SortOrderRequest $request, Page $page): RedirectResponse
    {
        $this->sorter->move($page, $request->validated('direction'));

        return back();
    }

    public function toggle(Page $page): RedirectResponse
    {
        $page->update(['is_published' => ! $page->is_published]);

        return back()->with('status', 'Halaman '.$page->title.($page->is_published ? ' diterbitkan.' : ' dijadikan draf.'));
    }

    public function destroy(Page $page): RedirectResponse
    {
        if ($page->system_key !== null) {
            return back()->with('error', 'Halaman sistem tidak bisa dihapus.');
        }

        $page->delete();

        return back()->with('status', 'Halaman dihapus.');
    }

    private function save(Page $page, array $data): void
    {
        DB::transaction(function () use ($page, $data) {
            $page->fill(Arr::except($data, 'sort_order'));
            $this->sorter->placeAt($page, (int) $data['sort_order']);
        });
    }
}
