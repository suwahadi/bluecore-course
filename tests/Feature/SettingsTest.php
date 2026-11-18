<?php

use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

it('returns defaults when nothing is stored', function () {
    expect(Settings::get('site_name'))->toBe('BlueCore Course')
        ->and(Settings::get('admin_fee'))->toBe(0)
        ->and(Settings::get('address'))->toBe('Bogor')
        ->and(Settings::get('whatsapp'))->toBe('6281818182878')
        ->and(Settings::get('email'))->toBe('support@bluecore.co.id')
        ->and(Settings::get('instagram_url'))->toBe('https://instagram.com/bluecore.official')
        ->and(Settings::get('shopee_url'))->toBeNull();
});

it('reads a new value right after saving', function () {
    Settings::get('site_name');

    Settings::set(['site_name' => 'Kursus Galon', 'admin_fee' => 2500, 'unknown' => 'x']);

    expect(Settings::get('site_name'))->toBe('Kursus Galon')
        ->and(Settings::get('admin_fee'))->toBe(2500)
        ->and(Setting::where('key', 'unknown')->exists())->toBeFalse();
});

it('starts every test with fresh values', function () {
    expect(Settings::get('site_name'))->toBe('BlueCore Course');
});

it('lists every configured key', function () {
    expect(array_keys(Settings::all()))->toEqualCanonicalizing(array_keys(config('settings')));
});

it('rejects unknown keys', function () {
    Settings::get('nope');
})->throws(InvalidArgumentException::class);

it('queries the database at most once per request', function () {
    Cache::flush();
    DB::enableQueryLog();

    Settings::get('site_name');
    Settings::get('email');
    Settings::get('admin_fee');

    $settingQueries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], '"settings"'));

    expect($settingQueries)->toHaveCount(1);
});
