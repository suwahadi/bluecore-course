<?php

namespace App\Support;

use App\Rules\RealImage;
use App\Rules\RealPdf;

class UploadRules
{
    public static function image(): array
    {
        return [
            'bail', 'nullable', 'file', 'max:4096', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp',
            'dimensions:min_width=200,min_height=200,max_width=6000,max_height=6000', new RealImage,
        ];
    }

    public static function imageMessages(string $field): array
    {
        return [
            $field.'.file' => 'File bukan gambar yang valid.',
            $field.'.uploaded' => 'Gambar gagal diunggah. Ukuran maksimal 4 MB.',
            $field.'.max' => 'Ukuran gambar maksimal 4 MB.',
            $field.'.mimes' => 'Gambar harus JPG, PNG, atau WebP.',
            $field.'.mimetypes' => 'Gambar harus JPG, PNG, atau WebP.',
            $field.'.dimensions' => 'Ukuran gambar minimal 200 x 200 piksel dan maksimal 6000 x 6000 piksel.',
        ];
    }

    public static function pdf(): array
    {
        return ['bail', 'required', 'file', 'max:20480', 'mimes:pdf', 'mimetypes:application/pdf', new RealPdf];
    }

    public static function pdfMessages(string $field): array
    {
        return collect(['required', 'file', 'uploaded', 'max', 'mimes', 'mimetypes'])
            ->mapWithKeys(fn (string $rule) => [$field.'.'.$rule => 'File harus PDF, maksimal 20 MB.'])
            ->all();
    }
}
