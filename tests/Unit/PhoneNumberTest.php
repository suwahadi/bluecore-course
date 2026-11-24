<?php

use App\Support\PhoneNumber;

it('removes spaces and hyphens', function () {
    expect(PhoneNumber::clean('+62 812-3456-789'))->toBe('+628123456789')
        ->and(PhoneNumber::clean(null))->toBe('');
});

dataset('phones', [
    'local format' => ['08123456789', true],
    'international without plus' => ['628123456789', true],
    'international with plus and separators' => ['+62 812-3456-789', true],
    'too short' => ['0812345', false],
    'not a mobile prefix' => ['0712345678', false],
    'too long' => ['08123456789012345', false],
    'letters inside' => ['08123abc789', false],
    'plus before zero' => ['+08123456789', false],
    'empty' => ['', false],
    'null' => [null, false],
]);

it('validates Indonesian mobile numbers', function (?string $phone, bool $valid) {
    expect(PhoneNumber::isValid($phone))->toBe($valid);
})->with('phones');

it('strips separators pasted from WhatsApp and contacts', function () {
    expect(PhoneNumber::clean("0812\u{00A0}3456\u{200E}789"))->toBe('08123456789')
        ->and(PhoneNumber::clean("\u{202A}+62 (812) 3456.789\u{202C}"))->toBe('+628123456789')
        ->and(PhoneNumber::isValid("+62\u{2066}812-3456-789\u{2069}"))->toBeTrue()
        ->and(PhoneNumber::clean("0812\u{200B}3456789"))->toBe('08123456789');
});

it('provides shared validation rules and messages', function () {
    expect(PhoneNumber::rules())->toContain('required')
        ->and(PhoneNumber::rules(false))->toContain('nullable')
        ->and(PhoneNumber::messages())->toBe([
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Nomor telepon belum benar. Gunakan format 08xx atau 628xx.',
        ]);
});
