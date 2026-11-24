<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class Slug
{
    public const PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public static function normalize(mixed $value): mixed
    {
        return is_string($value) ? Str::slug($value) : $value;
    }

    public static function rules(string $table, ?int $ignoreId = null): array
    {
        return ['required', 'string', 'max:120', 'regex:'.self::PATTERN, Rule::unique($table, 'slug')->ignore($ignoreId)];
    }

    public static function messages(): array
    {
        return [
            'slug.required' => 'Slug wajib diisi.',
            'slug.string' => 'Slug wajib diisi.',
            'slug.max' => 'Slug maksimal 120 karakter.',
            'slug.regex' => 'Slug hanya boleh huruf kecil, angka, dan tanda hubung.',
            'slug.unique' => 'Slug sudah dipakai.',
        ];
    }
}
