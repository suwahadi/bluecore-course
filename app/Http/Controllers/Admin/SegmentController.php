<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourseStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SegmentsRequest;
use App\Models\Course;
use App\Models\CourseSegment;
use App\Support\YouTubeUrl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class SegmentController extends Controller
{
    public function update(SegmentsRequest $request, Course $course): RedirectResponse
    {
        if ($request->input('editor') !== 'ready') {
            return back()->with('error', 'Editor video belum siap. Muat ulang halaman lalu coba lagi.');
        }

        $rows = $request->rows();

        if ($rows === [] && $course->status === CourseStatus::Published) {
            return back()->with('error', 'Kursus terbit minimal punya satu video. Jadikan draf dulu untuk menghapus semua video.');
        }

        DB::transaction(function () use ($course, $rows) {
            $keptIds = array_values(array_filter(array_column($rows, 'id')));
            CourseSegment::query()->where('course_id', $course->id)->whereNotIn('id', $keptIds)->delete();

            foreach ($rows as $index => $row) {
                $attributes = [
                    'title' => trim($row['title']),
                    'youtube_video_id' => YouTubeUrl::parse($row['url']),
                    'duration_seconds' => $this->duration($row),
                    'sort_order' => $index + 1,
                ];

                filled($row['id'] ?? null)
                    ? CourseSegment::query()->where('course_id', $course->id)->findOrFail($row['id'])->update($attributes)
                    : $course->segments()->create($attributes);
            }
        });

        return back()->with('status', 'Video kursus disimpan.');
    }

    private function duration(array $row): ?int
    {
        $minutes = $row['minutes'] ?? null;
        $seconds = $row['seconds'] ?? null;

        if (blank($minutes) && blank($seconds)) {
            return null;
        }

        return (int) $minutes * 60 + (int) $seconds;
    }
}
