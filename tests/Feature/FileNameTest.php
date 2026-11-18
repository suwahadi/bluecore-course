<?php

use App\Support\FileName;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

afterEach(fn () => Str::createRandomStringsNormally());

it('builds a 14 character lowercase name in the directory', function () {
    Storage::fake('public');

    expect(FileName::random('courses', 'webp', 'public'))->toMatch('/^courses\/[a-z0-9]{14}\.webp$/');
});

it('skips names that already exist on the disk', function () {
    Storage::fake('public');
    Storage::disk('public')->put('courses/aaaaaaaaaaaaaa.webp', 'x');
    Str::createRandomStringsUsingSequence(['AAAAAAAAAAAAAA', 'bbbbbbbbbbbbbb']);

    expect(FileName::random('courses', 'webp', 'public'))->toBe('courses/bbbbbbbbbbbbbb.webp');
});
