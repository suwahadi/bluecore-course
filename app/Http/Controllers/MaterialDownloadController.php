<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseMaterial;
use App\Support\FileName;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaterialDownloadController extends Controller
{
    public function __invoke(Course $course, CourseMaterial $material): StreamedResponse
    {
        Gate::authorize('learn', $course);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($material->file_path), 404);

        return $disk->download($material->file_path, FileName::forDownload($material->original_name), ['X-Content-Type-Options' => 'nosniff']);
    }
}
