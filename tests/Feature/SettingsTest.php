<?php

use App\Models\Setting;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

it('returns defaults when nothing is stored', function () {
    expect(Settings::get('site_name'))->toBe('Bluecore Course')
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
    expect(Settings::get('site_name'))->toBe('Bluecore Course');
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

it('casts form strings to each setting type', function () {
    Settings::set(['admin_fee' => '2500', 'featured_course_id' => '5', 'site_slogan' => 'Panen tiap bulan']);

    expect(Settings::get('admin_fee'))->toBe(2500)
        ->and(Settings::get('featured_course_id'))->toBe(5)
        ->and(Settings::get('site_slogan'))->toBe('Panen tiap bulan');
});

it('stores empty optional values as null', function () {
    Settings::set(['shopee_url' => '', 'featured_course_id' => '']);

    expect(Settings::get('shopee_url'))->toBeNull()
        ->and(Settings::get('featured_course_id'))->toBeNull();
});

it('declares a type for every setting', function () {
    foreach (config('settings') as $key => $definition) {
        expect($definition)->toHaveKey('type')
            ->and(in_array($definition['type'], ['string', 'int'], true))->toBeTrue("Setting {$key} has an unknown type");
    }
});
