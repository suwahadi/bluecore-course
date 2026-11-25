<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;

class Tracking
{
    public const SESSION_KEY = 'tracking_events';

    private const PIXEL_EVENTS = [
        'sign_up' => 'CompleteRegistration',
        'begin_checkout' => 'InitiateCheckout',
        'purchase' => 'Purchase',
    ];

    private const JSON_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    public static function configured(): bool
    {
        return filled(Settings::get('ga4_measurement_id')) || filled(Settings::get('meta_pixel_id'));
    }

    public static function push(string $name, array $params = []): void
    {
        if (! array_key_exists($name, self::PIXEL_EVENTS) || ! self::configured() || Auth::user()?->isAdmin()) {
            return;
        }

        session()->push(self::SESSION_KEY, ['name' => $name, 'params' => $params]);
    }

    public static function pull(): array
    {
        return collect((array) session()->pull(self::SESSION_KEY, []))
            ->map(fn ($event) => is_string($event) ? ['name' => $event, 'params' => []] : $event)
            ->filter(fn ($event) => is_array($event) && array_key_exists($event['name'] ?? null, self::PIXEL_EVENTS))
            ->values()
            ->all();
    }

    public static function gtagCall(array $event): string
    {
        $params = $event['params'] ?? [];

        return "gtag('event', '".$event['name']."'".($params === [] ? '' : ', '.json_encode($params, self::JSON_FLAGS)).')';
    }

    public static function pixelCall(array $event): string
    {
        $params = array_intersect_key($event['params'] ?? [], array_flip(['value', 'currency']));

        return "fbq('track', '".self::PIXEL_EVENTS[$event['name']]."'".($params === [] ? '' : ', '.json_encode($params, self::JSON_FLAGS)).')';
    }
}
