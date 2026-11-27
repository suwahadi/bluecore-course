<?php

use App\Models\Course;
use App\Models\Page;

it('lists public pages, published courses and published pages in the sitemap', function () {
    $course = Course::factory()->published()->create(['slug' => 'lele-galon']);
    $course->forceFill(['updated_at' => '2026-11-20 10:00:00'])->saveQuietly();
    Course::factory()->create(['slug' => 'draf-kursus']);
    Page::factory()->create(['slug' => 'tentang-kami']);
    Page::factory()->create(['slug' => 'draf-halaman', 'is_published' => false]);

    $response = $this->get('/sitemap.xml')->assertOk();

    expect($response->headers->get('content-type'))->toContain('application/xml');
    $xml = simplexml_load_string($response->getContent());
    $locations = [];
    foreach ($xml->url as $url) {
        $locations[] = (string) $url->loc;
    }

    expect($locations)->toContain(url('/'), url('/courses'), url('/contact'), url('/courses/lele-galon'), url('/tentang-kami'))
        ->not->toContain(url('/free-video'))
        ->not->toContain(url('/courses/draf-kursus'))
        ->not->toContain(url('/draf-halaman'))
        ->and($response->getContent())->toContain('<lastmod>2026-11-20</lastmod>');
});

it('serves robots.txt from the app URL', function () {
    config(['app.url' => 'https://course.bluecore.co.id']);

    $response = $this->get('/robots.txt')->assertOk();

    expect($response->headers->get('content-type'))->toContain('text/plain')
        ->and($response->getContent())->toContain('User-agent: *')
        ->toContain("Disallow: /admin\n")
        ->toContain("Disallow: /account\n")
        ->toContain("Disallow: /orders\n")
        ->toContain("Disallow: /order/\n")
        ->toContain("Disallow: /learn/\n")
        ->toContain("Disallow: /my-courses\n")
        ->toContain("Disallow: /payments/\n")
        ->toContain('Sitemap: https://course.bluecore.co.id/sitemap.xml')
        ->and(file_exists(public_path('robots.txt')))->toBeFalse();
});

it('keeps the canonical URL free of query strings', function () {
    $this->get('/courses?q=lele&utm_source=wa')
        ->assertSee('<link rel="canonical" href="'.url('/courses').'">', false)
        ->assertSee('<meta property="og:url" content="'.url('/courses').'">', false);
});
