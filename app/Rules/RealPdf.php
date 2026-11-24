<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class RealPdf implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $header = $value instanceof UploadedFile && $value->isValid() ? (string) file_get_contents($value->getRealPath(), false, null, 0, 5) : '';

        if ($header !== '%PDF-') {
            $fail('File harus PDF, maksimal 20 MB.');
        }
    }
}
