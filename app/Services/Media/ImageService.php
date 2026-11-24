<?php

namespace App\Services\Media;

use App\Support\FileName;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class ImageService
{
    private const WEBP_QUALITY = 80;

    public function storeWebp(UploadedFile $file, string $directory, int $maxWidth): string
    {
        $manager = new ImageManager(Driver::class, autoOrientation: true, strip: true);
        $encoded = $manager->decodePath($file->getRealPath())
            ->scaleDown(width: $maxWidth)
            ->encode(new WebpEncoder(quality: self::WEBP_QUALITY, strip: true));

        $path = FileName::random($directory, 'webp', 'public');
        Storage::disk('public')->put($path, $encoded->toString());

        return $path;
    }
}
