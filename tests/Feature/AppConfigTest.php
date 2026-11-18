<?php

it('uses Indonesian locale and Jakarta timezone', function () {
    expect(config('app.locale'))->toBe('id')
        ->and(config('app.fallback_locale'))->toBe('en')
        ->and(config('app.timezone'))->toBe('Asia/Jakarta');
});
