<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\Page;
use App\Services\Ordering\SortOrderService;
use Illuminate\Support\Facades\Cache;

function categoryOrder(): array
{
    return Category::query()->ordered()->get()->map(fn ($category) => $category->name.':'.$category->sort_order)->all();
}

it('moves an item up and renumbers everything from 1', function () {
    foreach (['Alfa', 'Bravo', 'Charlie'] as $name) {
        Category::factory()->create(['name' => $name, 'sort_order' => 1]);
    }

    app(SortOrderService::class)->move(Category::firstWhere('name', 'Charlie'), 'up');

    expect(categoryOrder())->toBe(['Alfa:1', 'Charlie:2', 'Bravo:3']);
});

it('moves an item down', function () {
    foreach (['Alfa' => 1, 'Bravo' => 2, 'Charlie' => 3] as $name => $order) {
        Category::factory()->create(['name' => $name, 'sort_order' => $order]);
    }

    app(SortOrderService::class)->move(Category::firstWhere('name', 'Alfa'), 'down');

    expect(categoryOrder())->toBe(['Bravo:1', 'Alfa:2', 'Charlie:3']);
});

it('leaves the order alone when moving past either end', function () {
    foreach (['Alfa' => 1, 'Bravo' => 2] as $name => $order) {
        Category::factory()->create(['name' => $name, 'sort_order' => $order]);
    }

    app(SortOrderService::class)->move(Category::firstWhere('name', 'Alfa'), 'up');
    app(SortOrderService::class)->move(Category::firstWhere('name', 'Bravo'), 'down');

    expect(categoryOrder())->toBe(['Alfa:1', 'Bravo:2']);
});

it('places an item at a position and renumbers', function () {
    foreach (['Alfa' => 1, 'Bravo' => 2, 'Charlie' => 3, 'Delta' => 4] as $name => $order) {
        Category::factory()->create(['name' => $name, 'sort_order' => $order]);
    }

    app(SortOrderService::class)->placeAt(Category::firstWhere('name', 'Delta'), 2);

    expect(categoryOrder())->toBe(['Alfa:1', 'Delta:2', 'Bravo:3', 'Charlie:4']);
});

it('clamps a position outside the list to its ends', function () {
    foreach (['Alfa' => 1, 'Bravo' => 2, 'Charlie' => 3] as $name => $order) {
        Category::factory()->create(['name' => $name, 'sort_order' => $order]);
    }

    app(SortOrderService::class)->placeAt(Category::firstWhere('name', 'Alfa'), 99);
    app(SortOrderService::class)->placeAt(new Category(['name' => 'Baru', 'slug' => 'baru']), 0);

    expect(categoryOrder())->toBe(['Baru:1', 'Bravo:2', 'Charlie:3', 'Alfa:4']);
});

it('fires model events while renumbering', function () {
    $first = Page::factory()->create(['sort_order' => 1]);
    Page::factory()->create(['sort_order' => 2]);
    Cache::forever(Page::FOOTER_CACHE_KEY, 'stale');

    app(SortOrderService::class)->move($first, 'down');

    expect(Cache::has(Page::FOOTER_CACHE_KEY))->toBeFalse();
});

it('only renumbers materials of the same course', function () {
    [$course, $other] = Course::factory()->count(2)->create();
    $first = CourseMaterial::factory()->for($course)->create(['sort_order' => 1]);
    $second = CourseMaterial::factory()->for($course)->create(['sort_order' => 2]);
    $untouched = CourseMaterial::factory()->for($other)->create(['sort_order' => 7]);

    app(SortOrderService::class)->move($second, 'up');

    expect($second->fresh()->sort_order)->toBe(1)
        ->and($first->fresh()->sort_order)->toBe(2)
        ->and($untouched->fresh()->sort_order)->toBe(7);
});

it('moves an item down even when every sort order is equal', function () {
    foreach (['Alfa', 'Bravo', 'Charlie'] as $name) {
        Category::factory()->create(['name' => $name, 'sort_order' => 1]);
    }

    app(SortOrderService::class)->move(Category::firstWhere('name', 'Alfa'), 'down');

    expect(categoryOrder())->toBe(['Bravo:1', 'Alfa:2', 'Charlie:3']);
});
