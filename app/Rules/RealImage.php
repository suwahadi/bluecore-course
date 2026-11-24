<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class RealImage implements ValidationRule
{
    public const MAX_PIXELS = 36_000_000;

    private const ALLOWED_TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $info = $value instanceof UploadedFile && $value->isValid() ? @getimagesize($value->getRealPath()) : false;

        if ($info === false || ! in_array($info[2], self::ALLOWED_TYPES, true) || $info[0] * $info[1] > self::MAX_PIXELS || ! $this->decodes($value, $info[2])) {
            $fail('File bukan gambar yang valid.');
        }
    }

    private function decodes(UploadedFile $file, int $type): bool
    {
        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG => @imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file->getRealPath()),
        };

        return $image !== false;
    }
}
