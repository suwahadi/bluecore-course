<?php

use App\Livewire\CourseCatalog;
use App\Models\Category;
use App\Models\Course;
use App\Models\Enrollment;
use Livewire\Livewire;

beforeEach(function () {
    $this->lele = Category::factory()->create(['name' => 'Lele', 'slug' => 'lele', 'sort_order' => 2]);
    $this->nila = Category::factory()->create(['name' => 'Nila', 'slug' => 'nila', 'sort_order' => 1]);
    $this->hias = Category::factory()->create(['name' => 'Ikan Hias', 'slug' => 'ikan-hias', 'is_active' => false]);
});

function catalogCourse(string $title, Category $category, int $price, int $daysAgo, int $buyers = 0): Course
{
    $course = Course::factory()->published()->for($category)->create(['title' => $title, 'price' => $price, 'published_at' => now()->subDays($daysAgo)]);
    Enrollment::factory()->count($buyers)->for($course)->create();

    return $course;
}

it('renders the catalog page with the heading and course count', function () {
    catalogCourse('Lele Galon', $this->lele, 150000, 1);
    Course::factory()->create(['title' => 'Draf Rahasia']);

    $this->get('/courses')
        ->assertOk()
        ->assertSee('<h1>Semua kursus</h1>', false)
        ->assertSeeText('1 kursus tersedia. Cari judul, saring kategori, atau urutkan.')
        ->assertSeeText('Lele Galon')
        ->assertDontSeeText('Draf Rahasia')
        ->assertSee('placeholder="misalnya lele"', false);
});

it('searches titles without caring about case', function () {
    catalogCourse('Budidaya Lele Galon', $this->lele, 150000, 1);
    catalogCourse('Nila Merah', $this->nila, 90000, 2);

    Livewire::test(CourseCatalog::class)
        ->set('q', 'LELE')
        ->assertSee('Budidaya Lele Galon')
        ->assertDontSee('Nila Merah');
});

it('filters by category slug and only offers active categories', function () {
    catalogCourse('Lele Galon', $this->lele, 150000, 1);
    catalogCourse('Nila Merah', $this->nila, 90000, 2);
    catalogCourse('Cupang Hias', $this->hias, 50000, 3);

    Livewire::test(CourseCatalog::class)
        ->assertSeeInOrder(['Semua kategori', 'Nila', 'Lele'])
        ->assertDontSeeHtml('<option value="ikan-hias"')
        ->assertSee('Cupang Hias')
        ->set('category', 'nila')
        ->assertSee('Nila Merah')
        ->assertDontSee('Lele Galon');
});

it('keeps courses of inactive categories in search results', function () {
    catalogCourse('Cupang Hias', $this->hias, 50000, 3);

    Livewire::test(CourseCatalog::class)->set('q', 'cupang')->assertSee('Cupang Hias');
});

it('sorts by newest, cheapest, priciest and most popular', function (string $sort, array $order) {
    catalogCourse('Tengah', $this->lele, 100000, 2, 1);
    catalogCourse('Baru', $this->lele, 150000, 1, 0);
    catalogCourse('Lama', $this->lele, 50000, 3, 3);

    Livewire::test(CourseCatalog::class)->set('sort', $sort)->assertSeeInOrder($order);
})->with([
    'newest' => ['newest', ['Baru', 'Tengah', 'Lama']],
    'cheapest' => ['cheapest', ['Lama', 'Tengah', 'Baru']],
    'priciest' => ['priciest', ['Baru', 'Tengah', 'Lama']],
    'popular' => ['popular', ['Lama', 'Tengah', 'Baru']],
]);

it('reads the filters from the query string', function () {
    catalogCourse('Lele Galon', $this->lele, 150000, 1);
    catalogCourse('Lele Murah', $this->lele, 50000, 2);
    catalogCourse('Nila Merah', $this->nila, 90000, 3);

    $this->get('/courses?q=lele&cat=lele&sort=cheapest')
        ->assertSeeInOrder(['Lele Murah', 'Lele Galon'])
        ->assertDontSeeText('Nila Merah');
});

it('pages by twelve and hides the pager for twelve or fewer', function () {
    foreach (range(1, 12) as $index) {
        catalogCourse('Kursus '.str_pad($index, 2, '0', STR_PAD_LEFT), $this->lele, 100000, $index);
    }

    Livewire::test(CourseCatalog::class)->assertDontSee('Berikutnya');

    catalogCourse('Kursus 13', $this->lele, 100000, 13);

    Livewire::test(CourseCatalog::class)
        ->assertSee('Halaman 1 dari 2')
        ->assertSee('Berikutnya')
        ->assertDontSee('Kursus 13')
        ->call('nextPage')
        ->assertSee('Kursus 13');
});

it('shows an empty state that clears every filter', function () {
    catalogCourse('Lele Galon', $this->lele, 150000, 1);

    Livewire::test(CourseCatalog::class)
        ->set('q', 'paus')
        ->set('sort', 'cheapest')
        ->assertSee('Tidak ada kursus yang cocok.')
        ->assertSee('Hapus pencarian dan filter')
        ->call('clearFilters')
        ->assertSet('q', '')
        ->assertSet('category', '')
        ->assertSet('sort', 'newest')
        ->assertSee('Lele Galon');
});

it('ignores unknown or malformed query values', function (string $query) {
    catalogCourse('Lele Galon', $this->lele, 150000, 1);

    $this->get('/courses?'.$query)->assertOk()->assertSeeText('Lele Galon');
})->with([
    'unknown category' => ['cat=tidak-ada'],
    'inactive category' => ['cat=ikan-hias'],
    'unknown sort' => ['sort=acak'],
    'array search' => ['q[]=x'],
    'array sort' => ['sort[]=x'],
    'page beyond the end' => ['page=999'],
]);

it('marks courses the visitor owns', function () {
    $course = catalogCourse('Lele Galon', $this->lele, 150000, 1);
    $enrollment = Enrollment::factory()->for($course)->create();

    $this->actingAs($enrollment->user)->get('/courses')->assertSeeText('Dimiliki');
});

it('writes normalised filters back to the address', function () {
    Livewire::withQueryParams(['sort' => 'acak', 'cat' => 'tidak-ada', 'q' => str_repeat('a', 150)])
        ->test(CourseCatalog::class)
        ->assertSet('sort', 'newest')
        ->assertSet('category', '')
        ->assertSet('q', str_repeat('a', 100));
});

it('ignores filters sent as lists', function () {
    Livewire::withQueryParams(['sort' => ['x'], 'cat' => ['y'], 'q' => ['z']])
        ->test(CourseCatalog::class)
        ->assertOk()
        ->assertSet('sort', 'newest')
        ->assertSet('category', '')
        ->assertSet('q', '');
});

it('scrolls back to the top of the catalog when the page changes', function () {
    Course::factory()->published()->count(13)->create();

    Livewire::test(CourseCatalog::class)
        ->assertSeeHtml('wire:click="nextPage(\'page\')" x-on:click="$wire.$el.scrollIntoView({ block: \'start\' })"')
        ->assertSeeHtml('wire:click="previousPage(\'page\')" x-on:click="$wire.$el.scrollIntoView({ block: \'start\' })"');
});

it('keeps a numeric search or category from the address', function () {
    Category::factory()->create(['name' => 'Angka', 'slug' => '2026']);

    Livewire::withQueryParams(['q' => 2026, 'cat' => 2026])
        ->test(CourseCatalog::class)
        ->assertSet('q', '2026')
        ->assertSet('category', '2026');
});
