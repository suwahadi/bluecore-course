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
