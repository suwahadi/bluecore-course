<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaterialRequest;
use App\Http\Requests\Admin\SortOrderRequest;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Services\Media\FileCleanup;
use App\Services\Media\PdfService;
use App\Services\Ordering\SortOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class MaterialController extends Controller
{
    public function __construct(private PdfService $pdfs, private SortOrderService $sorter)
    {
    }

    public function store(MaterialRequest $request, Course $course): RedirectResponse
    {
        $stored = $this->pdfs->store($request->file('file'));

        FileCleanup::onFailure('local', $stored['path'], fn () => DB::transaction(fn () => $course->materials()->create([
            'title' => $request->validated('title'),
            'file_path' => $stored['path'],
            'original_name' => $stored['original_name'],
            'file_size' => $stored['size'],
            'sort_order' => (int) $course->materials()->max('sort_order') + 1,
        ])));

        return back()->with('status', 'Materi ditambahkan.');
    }

    public function update(MaterialRequest $request, Course $course, CourseMaterial $material): RedirectResponse
    {
        $stored = $request->hasFile('file') ? $this->pdfs->store($request->file('file')) : null;

        FileCleanup::onFailure('local', $stored['path'] ?? null, fn () => DB::transaction(function () use ($request, $material, $stored) {
            $material->title = $request->validated('title');

            if ($stored !== null) {
                FileCleanup::afterCommit('local', $material->file_path);
                $material->fill([
                    'file_path' => $stored['path'],
                    'original_name' => $stored['original_name'],
                    'file_size' => $stored['size'],
                ]);
            }

            $material->save();
        }));

        return back()->with('status', 'Materi disimpan.');
    }

    public function move(SortOrderRequest $request, Course $course, CourseMaterial $material): RedirectResponse
    {
        $this->sorter->move($material, $request->validated('direction'));

        return back();
    }

    public function destroy(Course $course, CourseMaterial $material): RedirectResponse
    {
        DB::transaction(function () use ($material) {
            FileCleanup::afterCommit('local', $material->file_path);
            $material->delete();
        });

        return back()->with('status', 'Materi dihapus.');
    }
}
