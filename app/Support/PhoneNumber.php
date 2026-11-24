<?php

namespace App\Support;

class PhoneNumber
{
    public const PATTERN = '/^(\+?62|0)8[0-9]{7,12}$/';

    private const SEPARATORS = '/[\s\x{00A0}\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2066}-\x{2069}.()\-]/u';

    public static function clean(?string $value): string
    {
        return (string) preg_replace(self::SEPARATORS, '', (string) $value);
    }

    public static function isValid(?string $value): bool
    {
        return (bool) preg_match(self::PATTERN, self::clean($value));
    }

    public static function rules(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'regex:'.self::PATTERN];
    }

    public static function messages(string $field = 'phone'): array
    {
        return [
            $field.'.required' => 'Nomor telepon wajib diisi.',
            $field.'.regex' => 'Nomor telepon belum benar. Gunakan format 08xx atau 628xx.',
        ];
    }
}
