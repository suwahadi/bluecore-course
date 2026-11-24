<?php

use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = User::factory()->admin()->create();
    $this->course = Course::factory()->create();
});

function materialPdf(string $name = 'Panduan Galon.pdf', string $bytes = "%PDF-1.4\n%%EOF\n"): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'pdf');
    file_put_contents($path, $bytes.str_repeat(' ', 2048));

    return new UploadedFile($path, $name, 'application/pdf', null, true);
}

function materialUrl(?CourseMaterial $material = null, string $suffix = ''): string
{
    $base = '/admin/courses/'.test()->course->id.'/materials';

    return $material ? $base.'/'.$material->id.$suffix : $base;
}

it('adds a PDF at the end of the list and stores it privately', function () {
    CourseMaterial::factory()->for($this->course)->create(['sort_order' => 1]);

    $this->actingAs($this->admin)->from(route('admin.courses.edit', $this->course))
        ->post(materialUrl(), ['_modal' => 'material-new', 'title' => 'Panduan galon', 'file' => materialPdf()])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'Materi ditambahkan.');

    $material = CourseMaterial::firstWhere('title', 'Panduan galon');
    expect($material)->sort_order->toBe(2)->original_name->toBe('Panduan Galon.pdf')->file_size->toBeGreaterThan(2000)
        ->and($material->file_path)->toMatch('#^materials/[a-z0-9]{14}\.pdf$#');
    Storage::disk('local')->assertExists($material->file_path);
});

it('shows dialog errors and stores nothing for a bad upload', function (array $input, string $field, string $message) {
    $html = $this->actingAs($this->admin)->from(route('admin.courses.edit', $this->course))->followingRedirects()
        ->post(materialUrl(), ['_modal' => 'material-new', 'title' => 'Panduan galon', 'file' => materialPdf(), ...$input])
        ->getContent();

    expect($html)->toContain('id="material-new-'.$field.'-error" role="alert">'.$message)
        ->and(CourseMaterial::query()->count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
})->with([
    'empty title' => [['title' => ''], 'title', 'Judul materi wajib diisi.'],
    'long title' => [['title' => str_repeat('a', 151)], 'title', 'Judul materi maksimal 150 karakter.'],
    'missing file' => [['file' => null], 'file', 'File harus PDF, maksimal 20 MB.'],
    'fake pdf' => [['file' => materialPdf('palsu.pdf', 'bukan pdf')], 'file', 'File harus PDF, maksimal 20 MB.'],
]);

it('keeps the course form free of a failed material title', function () {
    $html = $this->actingAs($this->admin)->from(route('admin.courses.edit', $this->course))->followingRedirects()
        ->post(materialUrl(), ['_modal' => 'material-new', 'title' => 'Judul materi salah', 'file' => null])
        ->getContent();

    expect($html)->toContain('value="'.e($this->course->title).'"');
});

it('replaces the file and deletes the old one after saving', function () {
    Storage::disk('local')->put('materials/lama.pdf', 'x');
    $material = CourseMaterial::factory()->for($this->course)->create(['file_path' => 'materials/lama.pdf']);

    $this->actingAs($this->admin)->from(route('admin.courses.edit', $this->course))
        ->put(materialUrl($material), ['_modal' => "material-{$material->id}", 'title' => 'Panduan baru', 'file' => materialPdf('baru.pdf')])
        ->assertSessionHas('status', 'Materi disimpan.');

    $material->refresh();
    expect($material)->title->toBe('Panduan baru')->original_name->toBe('baru.pdf');
    Storage::disk('local')->assertMissing('materials/lama.pdf');
    Storage::disk('local')->assertExists($material->file_path);
});

it('renames a material without touching its file', function () {
    Storage::disk('local')->put('materials/tetap.pdf', 'x');
    $material = CourseMaterial::factory()->for($this->course)->create(['file_path' => 'materials/tetap.pdf']);

    $this->actingAs($this->admin)->from(route('admin.courses.edit', $this->course))
        ->put(materialUrl($material), ['_modal' => "material-{$material->id}", 'title' => 'Nama baru'])
        ->assertSessionHasNoErrors();

    expect($material->fresh())->title->toBe('Nama baru')->file_path->toBe('materials/tetap.pdf');
    Storage::disk('local')->assertExists('materials/tetap.pdf');
});

it('moves materials within the course', function () {
    $first = CourseMaterial::factory()->for($this->course)->create(['sort_order' => 1]);
    $second = CourseMaterial::factory()->for($this->course)->create(['sort_order' => 2]);

    $this->actingAs($this->admin)->from(route('admin.courses.edit', $this->course))
        ->post(materialUrl($second, '/move'), ['direction' => 'up']);

    expect($second->fresh()->sort_order)->toBe(1)->and($first->fresh()->sort_order)->toBe(2);
});

it('deletes a material with its file', function () {
    Storage::disk('local')->put('materials/hapus.pdf', 'x');
    $material = CourseMaterial::factory()->for($this->course)->create(['title' => 'Panduan galon', 'file_path' => 'materials/hapus.pdf']);

    $this->actingAs($this->admin)->get(route('admin.courses.edit', $this->course))->assertSeeText('Materi Panduan galon akan dihapus permanen.');
    $this->from(route('admin.courses.edit', $this->course))->delete(materialUrl($material))->assertSessionHas('status', 'Materi dihapus.');

    expect($material->fresh())->toBeNull();
    Storage::disk('local')->assertMissing('materials/hapus.pdf');
});

it('answers 404 for a material of another course', function () {
    $foreign = CourseMaterial::factory()->create();
    $this->actingAs($this->admin);

    $this->put(materialUrl($foreign), ['title' => 'Bajak'])->assertNotFound();
    $this->delete(materialUrl($foreign))->assertNotFound();
    $this->post(materialUrl($foreign, '/move'), ['direction' => 'up'])->assertNotFound();
    expect($foreign->fresh())->not->toBeNull();
});

it('lists the materials with title, original name and size', function () {
    CourseMaterial::factory()->for($this->course)->create(['title' => 'Panduan galon', 'original_name' => 'panduan.pdf', 'file_size' => 1_258_291]);

    $this->actingAs($this->admin)->get(route('admin.courses.edit', $this->course))
        ->assertSeeText('Materi PDF')
        ->assertSeeText('Panduan galon')
        ->assertSeeText('panduan.pdf')
        ->assertSeeText('1,2 MB')
        ->assertSee('aria-label="Ganti file"', false)
        ->assertSeeText('Tambah materi');
});
