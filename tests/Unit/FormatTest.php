<?php

use App\Support\Format;
use Carbon\CarbonImmutable;

it('formats rupiah with dot thousands', function () {
    expect(Format::rupiah(125000))->toBe('Rp 125.000')
        ->and(Format::rupiah(2500))->toBe('Rp 2.500')
        ->and(Format::rupiah(0))->toBe('Rp 0');
});

it('formats datetime in Jakarta time with Indonesian names', function () {
    $utc = CarbonImmutable::parse('2026-10-08 09:22:00', 'UTC');

    expect(Format::datetime($utc))->toBe('Kamis, 8 Okt 2026 16:22');
});

it('formats durations in hours and minutes', function () {
    expect(Format::duration(8100))->toBe('2 jam 15 menit')
        ->and(Format::duration(2700))->toBe('45 menit')
        ->and(Format::duration(7200))->toBe('2 jam')
        ->and(Format::duration(59))->toBe('1 menit')
        ->and(Format::duration(0))->toBe('0 menit');
});

it('formats file sizes in KB and MB', function () {
    expect(Format::fileSize(839680))->toBe('820 KB')
        ->and(Format::fileSize(1468006))->toBe('1,4 MB')
        ->and(Format::fileSize(500))->toBe('1 KB');
});
