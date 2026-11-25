<?php

namespace App\Support;

class PersonName
{
    private const HIDDEN_CHARACTERS = '/[\p{Cc}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}]/u';

    public static function rules(): array
    {
        return ['required', 'string', 'min:2', 'max:100', 'not_regex:'.self::HIDDEN_CHARACTERS];
    }

    public static function messages(string $field = 'name'): array
    {
        return [
            $field.'.required' => 'Nama wajib diisi.',
            $field.'.string' => 'Nama wajib diisi.',
            $field.'.min' => 'Nama minimal 2 karakter.',
            $field.'.max' => 'Nama maksimal 100 karakter.',
            $field.'.not_regex' => 'Nama hanya boleh berisi satu baris teks biasa.',
        ];
    }
}
