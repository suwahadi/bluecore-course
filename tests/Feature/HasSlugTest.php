<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\Page;

it('creates slugs from names with numeric suffixes', function () {
    $first = Category::factory()->create(['name' => 'Ikan Lele', 'slug' => null]);
    $second = Category::factory()->create(['name' => 'Ikan Lele!', 'slug' => null]);
    $third = Category::factory()->create(['name' => 'Ikan  Lele', 'slug' => null]);

    expect([$first->slug, $second->slug, $third->slug])->toBe(['ikan-lele', 'ikan-lele-2', 'ikan-lele-3']);
});

it('falls back to a random slug when the title has no letters', function () {
    $page = Page::factory()->create(['title' => '!!! 🐟', 'slug' => null]);

    expect($page->slug)->toMatch('/^[a-z0-9]{8}$/');
});

it('keeps a manual slug and never changes it when the title changes', function () {
    $page = Page::factory()->create(['title' => 'Tentang Kami', 'slug' => 'about-us']);

    $page->update(['title' => 'Profil Kami']);

    expect($page->fresh()->slug)->toBe('about-us');
});

it('builds course slugs from the title', function () {
    $course = Course::factory()->create(['title' => 'Budidaya Lele dalam Galon', 'slug' => null]);

    expect($course->slug)->toBe('budidaya-lele-dalam-galon');
});
