<?php

namespace App\Services\Courses;

use App\Models\Course;
use App\Services\Media\FileCleanup;
use App\Services\Media\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CourseService
{
    public const THUMBNAIL_WIDTH = 1200;

    public function __construct(private ImageService $images)
    {
    }

    public function save(Course $course, array $data, ?UploadedFile $thumbnail): Course
    {
        $newThumbnail = $thumbnail ? $this->images->storeWebp($thumbnail, 'courses', self::THUMBNAIL_WIDTH) : null;

        return FileCleanup::onFailure('public', $newThumbnail, fn () => DB::transaction(function () use ($course, $data, $newThumbnail) {
            $course->fill($data);

            if ($newThumbnail !== null) {
                FileCleanup::afterCommit('public', $course->getOriginal('thumbnail_path'));
                $course->thumbnail_path = $newThumbnail;
            }

            $course->save();

            return $course;
        }));
    }

    public function delete(Course $course): bool
    {
        if ($course->orders()->exists()) {
            return false;
        }

        DB::transaction(function () use ($course) {
            FileCleanup::afterCommit('local', ...$course->materials()->pluck('file_path')->all());
            FileCleanup::afterCommit('public', $course->thumbnail_path);
            $course->delete();
        });

        return true;
    }
}
