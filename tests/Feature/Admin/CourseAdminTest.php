<?php

use App\Enums\CourseStatus;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\CourseSegment;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    Storage::fake('local');
    $this->admin = User::factory()->admin()->create();
    $this->category = Category::factory()->create(['name' => 'Lele']);
});

function thumbnailUpload(int $width = 1600, int $height = 900): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    $path = tempnam(sys_get_temp_dir(), 'thumb');
    imagejpeg($image, $path);

    return new UploadedFile($path, 'sampul.jpg', 'image/jpeg', null, true);
}

function validCourse(array $overrides = []): array
{
    return [
        '_modal' => 'course-new',
        'title' => 'Budidaya Lele Galon',
        'slug' => 'budidaya-lele-galon',
        'category_id' => test()->category->id,
        'price' => 150000,
        'compare_at_price' => 200000,
        'short_description' => 'Mulai dari satu galon.',
        'description' => "Paragraf satu.\n\nParagraf dua.",
        'level' => ' Pemula ',
        'badge' => 'Terlaris',
        'rating' => '4.8',
        'fish_type' => 'Ikan Lele',
        ...$overrides,
    ];
}

it('lists courses with category, prices, counts and status', function () {
    $course = Course::factory()->published()->for($this->category)->create(['title' => 'Lele Galon', 'price' => 125000, 'compare_at_price' => 175000]);
    CourseSegment::factory()->count(3)->for($course)->create();
    Enrollment::factory()->count(2)->for($course)->create();
    Course::factory()->for($this->category)->create(['title' => 'Nila Galon']);

    $this->actingAs($this->admin)->get('/admin/courses')
        ->assertOk()
        ->assertSeeText('Lele Galon')
        ->assertSeeText('Rp 125.000')
        ->assertSeeText('Rp 175.000')
        ->assertSeeText('Terbit')
        ->assertSeeText('Draf')
        ->assertSee('<td class="num">3</td>', false)
        ->assertSee('<td class="num">2</td>', false)
        ->assertSee('aria-label="Jadikan draf"', false)
        ->assertSee('href="'.route('admin.courses.edit', $course).'"', false)
        ->assertSeeText('Segmen video dan materi PDF ditambahkan setelah kursus tersimpan sebagai draf.')
        ->assertSeeText('Simpan draf');
});

it('marks inactive categories in the course form', function () {
    Category::factory()->create(['name' => 'Gurame', 'is_active' => false]);

    $this->actingAs($this->admin)->get('/admin/courses')->assertSeeText('Gurame (nonaktif)');
});

it('refuses non-admins', function () {
    $this->actingAs(User::factory()->create())->post('/admin/courses', validCourse())->assertForbidden();
});

it('creates a draft with a WebP thumbnail and opens its edit page', function () {
    $response = $this->actingAs($this->admin)->from('/admin/courses')
        ->post('/admin/courses', validCourse(['thumbnail' => thumbnailUpload()]));

    $course = Course::firstWhere('slug', 'budidaya-lele-galon');
    $response->assertRedirect(route('admin.courses.edit', $course))->assertSessionHas('status', 'Kursus disimpan sebagai draf.');
    expect($course)->status->toBe(CourseStatus::Draft)->published_at->toBeNull()->price->toBe(150000)->level->toBe('Pemula')
        ->badge->toBe('Terlaris')->rating->toBe(4.8)->fish_type->toBe('Ikan Lele')
        ->and($course->thumbnail_path)->toMatch('#^courses/[a-z0-9]{14}\.webp$#');
    Storage::disk('public')->assertExists($course->thumbnail_path);
});

it('shows the PRD messages inside the dialog that failed', function (array $input, string $field, string $message) {
    $html = $this->actingAs($this->admin)->from('/admin/courses')->followingRedirects()
        ->post('/admin/courses', validCourse($input))->getContent();

    expect($html)->toContain('id="course-new-'.$field.'-error" role="alert">'.$message)
        ->and(Course::query()->count())->toBe(0);
})->with([
    'short title' => [['title' => 'A'], 'title', 'Judul wajib diisi, minimal 2 karakter.'],
    'zero price' => [['price' => 0], 'price', 'Harga wajib lebih dari 0.'],
    'empty price' => [['price' => ''], 'price', 'Harga wajib lebih dari 0.'],
    'compare not higher' => [['compare_at_price' => 150000], 'compare_at_price', 'Harga coret harus lebih besar dari harga.'],
    'long short description' => [['short_description' => str_repeat('a', 301)], 'short_description', 'Deskripsi singkat maksimal 300 karakter.'],
    'missing category' => [['category_id' => ''], 'category_id', 'Pilih kategori kursus.'],
    'long level' => [['level' => str_repeat('a', 41)], 'level', 'Level maksimal 40 karakter.'],
    'long badge' => [['badge' => str_repeat('a', 31)], 'badge', 'Label sorotan maksimal 30 karakter.'],
    'rating above five' => [['rating' => '5.1'], 'rating', 'Rating harus angka 1 sampai 5, contoh 4.8.'],
    'rating below one' => [['rating' => '0.5'], 'rating', 'Rating harus angka 1 sampai 5, contoh 4.8.'],
    'rating not a number' => [['rating' => 'bagus'], 'rating', 'Rating harus angka 1 sampai 5, contoh 4.8.'],
    'long fish type' => [['fish_type' => str_repeat('a', 41)], 'fish_type', 'Jenis ikan maksimal 40 karakter.'],
    'fake thumbnail' => [['thumbnail' => UploadedFile::fake()->create('sampul.jpg', 10, 'text/plain')], 'thumbnail', 'Gambar harus JPG, PNG, atau WebP.'],
]);

it('stores no file when the course fails validation', function () {
    $this->actingAs($this->admin)->from('/admin/courses')->post('/admin/courses', validCourse(['title' => '', 'thumbnail' => thumbnailUpload()]));

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('shows the edit page with the current values', function () {
    $course = Course::factory()->for($this->category)->create(['title' => 'Lele Galon', 'price' => 99000]);

    $this->actingAs($this->admin)->get(route('admin.courses.edit', $course))
        ->assertOk()
        ->assertSee('value="Lele Galon"', false)
        ->assertSee('value="99000"', false)
        ->assertDontSeeText('Harus lebih besar dari harga.')
        ->assertSeeText('Simpan kursus');
});

it('replaces the thumbnail and removes the old file after saving', function () {
    Storage::disk('public')->put('courses/lama.webp', 'x');
    $course = Course::factory()->for($this->category)->create(['thumbnail_path' => 'courses/lama.webp']);

    $this->actingAs($this->admin)->from(route('admin.courses.edit', $course))
        ->put(route('admin.courses.update', $course), validCourse(['_modal' => null, 'slug' => $course->slug, 'thumbnail' => thumbnailUpload()]))
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'Kursus disimpan.');

    Storage::disk('public')->assertMissing('courses/lama.webp');
    Storage::disk('public')->assertExists($course->fresh()->thumbnail_path);
});

it('keeps the old thumbnail when the update fails', function () {
    Storage::disk('public')->put('courses/lama.webp', 'x');
    $course = Course::factory()->for($this->category)->create(['thumbnail_path' => 'courses/lama.webp']);

    $this->actingAs($this->admin)->from(route('admin.courses.edit', $course))
        ->put(route('admin.courses.update', $course), validCourse(['_modal' => null, 'price' => 0, 'thumbnail' => thumbnailUpload()]));

    Storage::disk('public')->assertExists('courses/lama.webp');
    expect(Storage::disk('public')->allFiles())->toBe(['courses/lama.webp']);
});

it('refuses to publish a course without videos', function () {
    $course = Course::factory()->for($this->category)->create();

    $this->actingAs($this->admin)->from('/admin/courses')->post(route('admin.courses.publish', $course))
        ->assertSessionHas('error', 'Kursus belum punya segmen. Tambahkan minimal satu video sebelum diterbitkan.');

    expect($course->fresh()->status)->toBe(CourseStatus::Draft);
});

it('publishes once and keeps the first publish date', function () {
    $course = Course::factory()->for($this->category)->create(['title' => 'Lele Galon']);
    CourseSegment::factory()->for($course)->create();

    $this->travelTo(now()->subDays(3));
    $this->actingAs($this->admin)->from('/admin/courses')->post(route('admin.courses.publish', $course))
        ->assertSessionHas('status', 'Lele Galon diterbitkan.');
    $firstPublished = $course->fresh()->published_at;
    $this->travelBack();

    $this->post(route('admin.courses.publish', $course))
        ->assertSessionHas('status', 'Lele Galon dikembalikan ke draf. Pembeli lama tetap bisa belajar.');
    $this->post(route('admin.courses.publish', $course));

    expect($course->fresh())->status->toBe(CourseStatus::Published)
        ->and($course->fresh()->published_at->equalTo($firstPublished))->toBeTrue();
});

it('deletes a course with its videos, materials and files', function () {
    Storage::disk('public')->put('courses/sampul.webp', 'x');
    Storage::disk('local')->put('materials/panduan.pdf', 'x');
    $course = Course::factory()->for($this->category)->create(['thumbnail_path' => 'courses/sampul.webp']);
    CourseSegment::factory()->for($course)->create();
    CourseMaterial::factory()->for($course)->create(['file_path' => 'materials/panduan.pdf']);

    $this->actingAs($this->admin)->from('/admin/courses')->delete(route('admin.courses.destroy', $course))
        ->assertSessionHas('status', 'Kursus dihapus.');

    expect($course->fresh())->toBeNull()
        ->and(CourseSegment::query()->count())->toBe(0)
        ->and(CourseMaterial::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing('courses/sampul.webp');
    Storage::disk('local')->assertMissing('materials/panduan.pdf');
});

it('refuses to delete a course that has orders and keeps its files', function () {
    Storage::disk('public')->put('courses/sampul.webp', 'x');
    $course = Course::factory()->for($this->category)->create(['thumbnail_path' => 'courses/sampul.webp']);
    Order::factory()->for($course)->create();

    $this->actingAs($this->admin)->from('/admin/courses')->delete(route('admin.courses.destroy', $course))
        ->assertSessionHas('error', 'Kursus sudah punya pesanan. Jadikan draf saja.');

    expect($course->fresh())->not->toBeNull();
    Storage::disk('public')->assertExists('courses/sampul.webp');
});

it('accepts a WebP thumbnail and stores it as WebP', function () {
    $image = imagecreatetruecolor(1600, 900);
    $path = tempnam(sys_get_temp_dir(), 'thumb');
    imagewebp($image, $path);
    $upload = new Illuminate\Http\UploadedFile($path, 'foto.webp', 'image/webp', null, true);

    $this->actingAs($this->admin)->from('/admin/courses')->post('/admin/courses', validCourse(['thumbnail' => $upload]))
        ->assertSessionHasNoErrors();

    $course = Course::firstWhere('slug', 'budidaya-lele-galon');
    expect($course->thumbnail_path)->toMatch('#^courses/[a-z0-9]{14}\.webp$#');
    Storage::disk('public')->assertExists($course->thumbnail_path);
});
