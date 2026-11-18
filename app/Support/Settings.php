<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;

class Settings
{
    private const CACHE_KEY = 'settings';

    private const REQUEST_KEY = 'settings.values';

    public static function get(string $key): mixed
    {
        $definitions = config('settings');

        if (! array_key_exists($key, $definitions)) {
            throw new InvalidArgumentException("Unknown setting [{$key}].");
        }

        $stored = static::stored();

        return array_key_exists($key, $stored) ? $stored[$key] : $definitions[$key]['default'];
    }

    public static function all(): array
    {
        return collect(config('settings'))->keys()->mapWithKeys(fn (string $key) => [$key => static::get($key)])->all();
    }

    public static function set(array $values): void
    {
        $definitions = config('settings');

        foreach (array_intersect_key($values, $definitions) as $key => $value) {
            $typed = static::cast($value, $definitions[$key]['type']);
            Setting::query()->updateOrCreate(['key' => $key], ['value' => json_encode($typed)]);
        }

        Cache::forget(self::CACHE_KEY);
        app()->forgetInstance(self::REQUEST_KEY);
    }

    private static function cast(mixed $value, string $type): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $type === 'int' ? (int) $value : (string) $value;
    }

    private static function stored(): array
    {
        if (app()->bound(self::REQUEST_KEY)) {
            return app(self::REQUEST_KEY);
        }

        $values = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()
            ->pluck('value', 'key')
            ->map(fn (?string $value) => $value === null ? null : json_decode($value, true))
            ->all());

        app()->instance(self::REQUEST_KEY, $values);

        return $values;
    }
}
