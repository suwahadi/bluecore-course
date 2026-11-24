<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseRequest;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseSegment;
use App\Services\Courses\CourseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(private CourseService $courses)
    {
    }

    public function index(): View
    {
        return view('admin.courses.index', [
            'courses' => Course::query()->with('category:id,name')->withCount(['segments', 'enrollments'])->latest()->paginate(20),
            'categoryOptions' => $this->categoryOptions(),
        ]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        $course = $this->courses->save(new Course(['status' => CourseStatus::Draft]), $request->safe()->except('thumbnail'), $request->file('thumbnail'));

        return redirect()->route('admin.courses.edit', $course)->with('status', 'Kursus disimpan sebagai draf.');
    }

    public function edit(Course $course): View
    {
        return view('admin.courses.edit', [
            'course' => $course->load(['segments', 'materials']),
            'categoryOptions' => $this->categoryOptions(),
            'segmentRows' => $this->segmentRows($course),
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        $this->courses->save($course, $request->safe()->except('thumbnail'), $request->file('thumbnail'));

        return back()->with('status', 'Kursus disimpan.');
    }

    public function publish(Course $course): RedirectResponse
    {
        if ($course->status === CourseStatus::Published) {
            $course->update(['status' => CourseStatus::Draft]);

            return back()->with('status', $course->title.' dikembalikan ke draf. Pembeli lama tetap bisa belajar.');
        }

        if (! $course->segments()->exists()) {
            return back()->with('error', 'Kursus belum punya segmen. Tambahkan minimal satu video sebelum diterbitkan.');
        }

        $course->update(['status' => CourseStatus::Published, 'published_at' => $course->published_at ?? now()]);

        return back()->with('status', $course->title.' diterbitkan.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        if (! $this->courses->delete($course)) {
            return back()->with('error', 'Kursus sudah punya pesanan. Jadikan draf saja.');
        }

        return redirect()->route('admin.courses.index')->with('status', 'Kursus dihapus.');
    }

    private function segmentRows(Course $course): array
    {
        $typed = old('segments');
        $errors = session('errors')?->getBag('default');

        $rows = is_array($typed)
            ? array_values(array_filter($typed, 'is_array'))
            : $course->segments->map(fn (CourseSegment $segment) => [
                'id' => $segment->id,
                'title' => $segment->title,
                'url' => 'https://youtu.be/'.$segment->youtube_video_id,
                'minutes' => $segment->duration_seconds === null ? '' : intdiv($segment->duration_seconds, 60),
                'seconds' => $segment->duration_seconds === null ? '' : $segment->duration_seconds % 60,
            ])->all();

        return array_map(function (array $row, int $index) use ($errors) {
            $fields = array_map(fn ($value) => is_scalar($value) ? $value : '', array_merge(
                ['id' => null, 'title' => '', 'url' => '', 'minutes' => '', 'seconds' => ''],
                array_intersect_key($row, array_flip(['id', 'title', 'url', 'minutes', 'seconds'])),
            ));

            return [...$fields, 'errors' => array_filter([
                'title' => $errors?->first("segments.{$index}.title"),
                'url' => $errors?->first("segments.{$index}.url") ?: $errors?->first("segments.{$index}.id"),
                'duration' => $errors?->first("segments.{$index}.minutes") ?: $errors?->first("segments.{$index}.seconds"),
            ])];
        }, $rows, array_keys($rows));
    }

    private function categoryOptions(): array
    {
        return Category::query()->ordered()->get(['id', 'name', 'is_active'])
            ->mapWithKeys(fn (Category $category) => [$category->id => $category->name.($category->is_active ? '' : ' (nonaktif)')])
            ->all();
    }
}
