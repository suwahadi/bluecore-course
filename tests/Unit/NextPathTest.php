<?php

use App\Support\NextPath;

dataset('next paths', [
    'checkout path' => ['/courses/lele/checkout', '/courses/lele/checkout'],
    'free video' => ['/free-video', '/free-video'],
    'path with query' => ['/orders?from=2026-10-01', '/orders?from=2026-10-01'],
    'absolute url' => ['https://evil.com', null],
    'protocol relative' => ['//evil.com', null],
    'backslash trick' => ['/\\evil.com', null],
    'tab trick' => ["/\t/evil.com", null],
    'newline trick' => ["/\n/evil.com", null],
    'javascript scheme' => ['javascript:alert(1)', null],
    'relative without slash' => ['courses', null],
    'too long' => ['/'.str_repeat('a', 2000), null],
    'empty' => ['', null],
    'null' => [null, null],
]);

it('accepts only local paths', function (?string $input, ?string $expected) {
    expect(NextPath::sanitize($input))->toBe($expected);
})->with('next paths');
