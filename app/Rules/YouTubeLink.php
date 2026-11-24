<?php

namespace App\Rules;

use App\Support\YouTubeUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class YouTubeLink implements ValidationRule
{
    public function __construct(private string $message = 'Tautan YouTube belum benar.')
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || YouTubeUrl::parse($value) === null) {
            $fail($this->message);
        }
    }
}
