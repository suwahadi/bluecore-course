<?php

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseSegment;
use App\Models\Testimonial;
use App\Support\Settings;
use App\Support\YouTubeUrl;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(fn () => Storage::fake('local'));

it('refuses to run in production', function () {
    $this->app->detectEnvironment(fn () => 'production');

    $this->artisan('db:seed', ['--class' => DemoSeeder::class, '--force' => true])->assertSuccessful();

    expect(Course::query()->count())->toBe(0)->and(Testimonial::query()->count())->toBe(0);
});

it('fills a local site with clearly marked demo content', function () {
    $this->seed(DemoSeeder::class);

    expect(Category::query()->count())->toBe(3)
        ->and(Course::query()->where('status', CourseStatus::Published)->count())->toBe(4)
        ->and(Course::query()->where('status', CourseStatus::Draft)->count())->toBe(1)
        ->and(CourseSegment::query()->where('youtube_video_id', '!=', 'M7lc1UVf-VE')->count())->toBe(0)
        ->and(Course::query()->published()->doesntHave('segments')->count())->toBe(0)
        ->and(Testimonial::query()->count())->toBe(4)
        ->and(Testimonial::query()->pluck('name')->unique()->all())->toBe(['[Nama pembeli]'])
        ->and(Testimonial::query()->pluck('quote')->unique()->all())->toBe(['[Tulis testimoni asli dari pembeli di sini]'])
        ->and(YouTubeUrl::parse(Settings::get('lead_magnet_youtube_url')))->toBe('M7lc1UVf-VE');

    $material = CourseMaterial::query()->firstOrFail();
    expect(Storage::disk('local')->get($material->file_path))->toStartWith('%PDF-')
        ->and($material->file_size)->toBe(Storage::disk('local')->size($material->file_path));
});

it('does not duplicate anything when run twice', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(Category::query()->count())->toBe(3)
        ->and(Course::query()->count())->toBe(5)
        ->and(CourseSegment::query()->count())->toBe(Course::query()->withCount('segments')->get()->sum('segments_count'))
        ->and(Testimonial::query()->count())->toBe(4)
        ->and(CourseMaterial::query()->count())->toBe(Course::query()->count());
});

it('keeps an existing free video setting', function () {
    Settings::set(['lead_magnet_youtube_url' => 'https://youtu.be/abcdefghijk']);

    $this->seed(DemoSeeder::class);

    expect(Settings::get('lead_magnet_youtube_url'))->toBe('https://youtu.be/abcdefghijk');
});

it('is not part of the default seeder', function () {
    $this->seed();

    expect(Course::query()->count())->toBe(0);
});
