<?php

namespace App\Services\Media;

use App\Support\FileName;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfService
{
    public function store(UploadedFile $file): array
    {
        $path = FileName::random('materials', 'pdf', 'local');
        Storage::disk('local')->putFileAs(dirname($path), $file, basename($path));

        return [
            'path' => $path,
            'size' => (int) $file->getSize(),
            'original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
        ];
    }
}
