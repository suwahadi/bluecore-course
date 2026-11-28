<?php

use App\Enums\CourseStatus;
use App\Models\Course;
use App\Models\CourseMaterial;
use App\Models\Enrollment;
use App\Models\User;
use App\Support\FileName;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->buyer = User::factory()->create();
    $this->course = Course::factory()->published()->create(['slug' => 'lele-galon']);
    $this->material = CourseMaterial::factory()->for($this->course)->create(['file_path' => 'materials/abcdefghijklmn.pdf', 'original_name' => 'Panduan Pakan.pdf']);
    Storage::disk('local')->put('materials/abcdefghijklmn.pdf', '%PDF-1.4 isi');
    Enrollment::factory()->for($this->buyer)->for($this->course)->create();
});

function downloadUrl(?CourseMaterial $material = null): string
{
    return '/courses/lele-galon/materials/'.($material ?? test()->material)->id;
}

it('downloads a material as an attachment for owners and admins', function () {
    $response = $this->actingAs($this->buyer)->get(downloadUrl())->assertOk();

    expect($response->headers->get('content-disposition'))->toStartWith('attachment;')->toContain('Panduan Pakan.pdf')
        ->and($response->headers->get('x-content-type-options'))->toBe('nosniff')
        ->and($response->streamedContent())->toBe('%PDF-1.4 isi');

    $this->actingAs(User::factory()->admin()->create())->get(downloadUrl())->assertOk();
});

it('refuses guests and users without access', function () {
    $this->get(downloadUrl())->assertStatus(401)->assertSee('next='.urlencode(downloadUrl()), false);
    $this->actingAs(User::factory()->create())->get(downloadUrl())->assertForbidden()->assertSeeText('Kamu belum punya akses ke kursus ini.');
});

it('keeps downloads open after the course goes back to draft', function () {
    $this->course->update(['status' => CourseStatus::Draft]);

    $this->actingAs($this->buyer)->get(downloadUrl())->assertOk();
});

it('returns 404 for a material of another course or a missing file', function () {
    $foreign = CourseMaterial::factory()->create();
    $missing = CourseMaterial::factory()->for($this->course)->create(['file_path' => 'materials/tidakadafile123.pdf']);

    $this->actingAs($this->buyer)->get(downloadUrl($foreign))->assertNotFound();
    $this->get(downloadUrl($missing))->assertNotFound();
});

it('sends a clean file name even when the stored name is odd', function () {
    $this->material->update(['original_name' => '../../"rahasia".pdf']);

    $response = $this->actingAs($this->buyer)->get(downloadUrl())->assertOk();

    expect($response->headers->get('content-disposition'))->toContain('rahasia.pdf')->not->toContain('..')->not->toContain('\\"');
});

it('cleans the download name', function (?string $original, string $expected) {
    expect(FileName::forDownload($original))->toBe($expected);
})->with([
    'path' => ['../../rahasia.pdf', 'rahasia.pdf'],
    'windows path' => ['C:\\Users\\sari\\modul.pdf', 'modul.pdf'],
    'quotes and capitals' => ['Pakan "baru".PDF', 'Pakan baru.PDF'],
    'control character, no extension' => ["Bab\x01 1", 'Bab 1.pdf'],
    'spaces and dots' => ['  Panduan   pakan.pdf. ', 'Panduan pakan.pdf'],
    'empty' => ['', 'materi.pdf'],
    'null' => [null, 'materi.pdf'],
    'dots only' => ['..', 'materi.pdf'],
    'too long' => [str_repeat('a', 300).'.pdf', str_repeat('a', 146).'.pdf'],
]);
