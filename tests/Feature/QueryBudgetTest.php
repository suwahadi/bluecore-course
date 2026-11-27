<?php

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseSegment;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\Page;
use App\Models\PaymentCallback;
use App\Models\Testimonial;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;

// Each page is measured twice: with a few rows and with three times as many.
// A growing count means a query runs per row (N+1).

function queriesFor(string $url): int
{
    test()->get($url)->assertOk();
    DB::enableQueryLog();
    DB::flushQueryLog();
    test()->get($url)->assertOk();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

function expectFlatQueries(string $url, Closure $grow): void
{
    $grow(3);
    $few = queriesFor($url);
    $grow(6);

    expect(queriesFor($url))->toBe($few, "The query count grew on {$url}");
}

function richCourse(array $attributes = []): Course
{
    $course = Course::factory()->published()->create($attributes);
    CourseSegment::factory()->count(2)->for($course)->create();
    CourseMaterial::factory()->for($course)->create();

    return $course;
}

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->admin = User::factory()->admin()->create();
});

it('keeps the catalog flat for an owner', function () {
    $this->actingAs($this->user);

    expectFlatQueries('/courses', function (int $n) {
        foreach (range(1, $n) as $i) {
            Enrollment::factory()->for($this->user)->for(richCourse())->create();
        }
    });
});

it('keeps the course page flat', function () {
    $course = richCourse(['slug' => 'lele-galon']);
    Enrollment::factory()->for($this->user)->for($course)->create();
    $this->actingAs($this->user);

    expectFlatQueries('/courses/lele-galon', function (int $n) use ($course) {
        CourseSegment::factory()->count($n)->for($course)->create();
        CourseMaterial::factory()->count($n)->for($course)->create();
        Enrollment::factory()->count($n)->for($course)->create();
    });
});

it('keeps a landing page flat', function () {
    Page::factory()->create(['slug' => 'lele-pemula', 'youtube_url' => 'https://youtu.be/M7lc1UVf-VE']);
    $this->actingAs($this->user);

    expectFlatQueries('/lele-pemula', function (int $n) {
        foreach (range(1, $n) as $i) {
            Enrollment::factory()->for($this->user)->for(richCourse())->create();
        }
    });
});

it('keeps the order list flat', function () {
    $this->actingAs($this->user);

    expectFlatQueries('/orders', fn (int $n) => Order::factory()->count($n)->for($this->user)->create());
});

it('keeps the order page flat for an admin', function () {
    $order = Order::factory()->for($this->user)->create(['status' => OrderStatus::Paid, 'paid_at' => now()]);
    $this->actingAs($this->admin);

    expectFlatQueries('/order/'.$order->uuid, fn (int $n) => PaymentCallback::factory()->count($n)->create([
        'order_id' => $order->id,
        'merchant_order_id' => $order->order_number,
    ]));
});

it('keeps the admin pages flat', function (string $url, Closure $grow) {
    $this->actingAs($this->admin);

    expectFlatQueries($url, $grow);
})->with([
    'dashboard' => ['/admin', function (int $n) {
        Order::factory()->count($n)->create();
        Order::factory()->paid()->count($n)->create();
    }],
    'orders' => ['/admin/orders', function (int $n) {
        Order::factory()->count($n)->create();
        Order::factory()->paid()->count($n)->create();
        Order::factory()->count($n)->create(['status' => OrderStatus::Expired]);
    }],
    'users' => ['/admin/users', function (int $n) {
        foreach (User::factory()->count($n)->create() as $user) {
            $course = richCourse();
            Enrollment::factory()->for($user)->for($course)->create();
            Order::factory()->paid()->for($user)->for($course)->create();
        }
    }],
    'courses' => ['/admin/courses', function (int $n) {
        foreach (range(1, $n) as $i) {
            Enrollment::factory()->for(richCourse())->create();
        }
        Course::factory()->count($n)->create();
    }],
    'categories' => ['/admin/categories', function (int $n) {
        foreach (Category::factory()->count($n)->create() as $category) {
            Course::factory()->for($category)->create();
        }
    }],
    'testimonials' => ['/admin/testimonials', fn (int $n) => Testimonial::factory()->count($n)->create()],
    'pages' => ['/admin/pages', fn (int $n) => Page::factory()->count($n)->create()],
]);

it('keeps the course editor flat', function () {
    $course = richCourse();
    $this->actingAs($this->admin);

    expectFlatQueries('/admin/courses/'.$course->id.'/edit', function (int $n) use ($course) {
        CourseSegment::factory()->count($n)->for($course)->create();
        CourseMaterial::factory()->count($n)->for($course)->create();
    });
});

it('keeps the sitemap flat', function () {
    expectFlatQueries('/sitemap.xml', function (int $n) {
        Course::factory()->published()->count($n)->create();
        Page::factory()->count($n)->create();
    });
});
