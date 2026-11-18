<?php

use App\Enums\CourseStatus;
use App\Enums\OrderStatus;
use App\Enums\SignupSource;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseSegment;
use App\Models\Page;
use App\Models\Testimonial;
use App\Models\User;

it('gives new users the user role and a direct signup source', function () {
    $user = User::factory()->create();

    expect($user->fresh()->role)->toBe(UserRole::User)
        ->and($user->fresh()->signup_source)->toBe(SignupSource::Direct)
        ->and($user->isAdmin())->toBeFalse()
        ->and(User::factory()->admin()->create()->isAdmin())->toBeTrue();
});

it('orders active categories by sort order then name', function () {
    Category::factory()->create(['name' => 'Bawal', 'sort_order' => 2]);
    Category::factory()->create(['name' => 'Nila', 'sort_order' => 1]);
    Category::factory()->create(['name' => 'Gurame', 'sort_order' => 1]);
    Category::factory()->inactive()->create(['name' => 'Koi', 'sort_order' => 1]);

    expect(Category::active()->ordered()->pluck('name')->all())->toBe(['Gurame', 'Nila', 'Bawal']);
});

it('labels order statuses in Indonesian', function () {
    expect(OrderStatus::Pending->label())->toBe('Menunggu pembayaran')
        ->and(OrderStatus::Paid->label())->toBe('Lunas')
        ->and(OrderStatus::Failed->label())->toBe('Gagal')
        ->and(OrderStatus::Expired->label())->toBe('Kedaluwarsa')
        ->and(OrderStatus::Cancelled->label())->toBe('Dibatalkan')
        ->and(OrderStatus::Refunded->label())->toBe('Dikembalikan');
});

it('reserves system slugs for pages', function () {
    expect(Page::RESERVED_SLUGS)->toContain('admin', 'courses', 'free-video', 'demo', 'login', 'my-courses', 'payments');
});

it('finds system pages by key', function () {
    Page::factory()->create(['title' => 'Syarat dan Ketentuan', 'slug' => 'terms-and-condition', 'system_key' => 'terms']);

    expect(Page::systemPage('terms')?->slug)->toBe('terms-and-condition')
        ->and(Page::systemPage('privacy'))->toBeNull();
});

it('lists published footer pages in order', function () {
    Page::factory()->create(['title' => 'B', 'sort_order' => 2]);
    Page::factory()->create(['title' => 'A', 'sort_order' => 1]);
    Page::factory()->create(['title' => 'Draf', 'is_published' => false]);
    Page::factory()->create(['title' => 'Tersembunyi', 'show_in_footer' => false]);

    expect(Page::published()->inFooter()->ordered()->pluck('title')->all())->toBe(['A', 'B']);
});

it('loads course segments and materials in order', function () {
    $course = Course::factory()->published()->create();
    CourseSegment::factory()->for($course)->create(['sort_order' => 2, 'title' => 'Dua']);
    CourseSegment::factory()->for($course)->create(['sort_order' => 1, 'title' => 'Satu']);
    CourseMaterial::factory()->for($course)->create(['sort_order' => 2, 'title' => 'Modul B']);
    CourseMaterial::factory()->for($course)->create(['sort_order' => 1, 'title' => 'Modul A']);

    expect($course->segments()->pluck('title')->all())->toBe(['Satu', 'Dua'])
        ->and($course->materials()->pluck('title')->all())->toBe(['Modul A', 'Modul B'])
        ->and($course->status)->toBe(CourseStatus::Published)
        ->and(Course::published()->count())->toBe(1);
});

it('shows only visible testimonials in order', function () {
    Testimonial::factory()->create(['name' => 'Kedua', 'sort_order' => 2]);
    Testimonial::factory()->create(['name' => 'Pertama', 'sort_order' => 1]);
    Testimonial::factory()->create(['name' => 'Disembunyikan', 'is_visible' => false]);

    expect(Testimonial::visible()->ordered()->pluck('name')->all())->toBe(['Pertama', 'Kedua']);
});
