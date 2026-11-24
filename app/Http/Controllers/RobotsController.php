<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    private const DISALLOWED = ['/admin', '/account', '/orders', '/order/', '/learn/', '/my-courses', '/payments/'];

    public function __invoke(): Response
    {
        $lines = [
            'User-agent: *',
            ...array_map(fn (string $path) => 'Disallow: '.$path, self::DISALLOWED),
            '',
            'Sitemap: '.rtrim((string) config('app.url'), '/').'/sitemap.xml',
        ];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
