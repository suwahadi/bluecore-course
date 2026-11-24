<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CategoryRequest;
use App\Http\Requests\Admin\SortOrderRequest;
use App\Models\Category;
use App\Services\Ordering\SortOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(private SortOrderService $sorter)
    {
    }

    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::query()->ordered()->withCount('courses')->paginate(20),
            'nextPosition' => Category::query()->count() + 1,
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $this->save(new Category, $request->validated());

        return back()->with('status', 'Kategori dibuat.');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $this->save($category, $request->validated());

        return back()->with('status', 'Kategori disimpan.');
    }

    public function move(SortOrderRequest $request, Category $category): RedirectResponse
    {
        $this->sorter->move($category, $request->validated('direction'));

        return back();
    }

    public function toggle(Category $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('status', 'Kategori '.$category->name.($category->is_active ? ' diaktifkan.' : ' dinonaktifkan.'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->courses()->exists()) {
            return back()->with('error', 'Kategori masih dipakai kursus. Pindahkan kursusnya dulu.');
        }

        $category->delete();

        return back()->with('status', 'Kategori dihapus.');
    }

    private function save(Category $category, array $data): void
    {
        DB::transaction(function () use ($category, $data) {
            $category->fill(Arr::except($data, 'sort_order'));
            $this->sorter->placeAt($category, (int) $data['sort_order']);
        });
    }
}
