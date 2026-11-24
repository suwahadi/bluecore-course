<?php

use App\Models\Course;
use App\Models\CourseSegment;
use App\Models\SegmentProgress;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->course = Course::factory()->create();
});

function saveSegments(array $segments)
{
    $course = test()->course;

    return test()->actingAs(test()->admin)->from(route('admin.courses.edit', $course))
        ->put(route('admin.courses.segments.update', $course), $segments === [] ? [] : ['segments' => $segments]);
}

function segmentRows(): array
{
    return test()->course->segments()->get()
        ->map(fn (CourseSegment $segment) => [$segment->sort_order, $segment->title, $segment->youtube_video_id, $segment->duration_seconds])
        ->all();
}

it('saves new rows with their video ids, durations and order', function () {
    saveSegments([
        ['title' => 'Menyiapkan galon', 'url' => 'https://youtu.be/dQw4w9WgXcQ', 'minutes' => '3', 'seconds' => '20'],
        ['title' => 'Memilih benih', 'url' => 'https://www.youtube.com/watch?v=abcdefghijk&t=5', 'minutes' => '', 'seconds' => '45'],
        ['title' => 'Pakan harian', 'url' => 'https://youtube.com/shorts/ABCDEFGHIJK', 'minutes' => '', 'seconds' => ''],
    ])->assertSessionHasNoErrors()->assertSessionHas('status', 'Video kursus disimpan.');

    expect(segmentRows())->toBe([
        [1, 'Menyiapkan galon', 'dQw4w9WgXcQ', 200],
        [2, 'Memilih benih', 'abcdefghijk', 45],
        [3, 'Pakan harian', 'ABCDEFGHIJK', null],
    ]);
});

it('keeps existing rows by id so progress survives a reorder', function () {
    $first = CourseSegment::factory()->for($this->course)->create(['title' => 'Satu', 'sort_order' => 1]);
    $second = CourseSegment::factory()->for($this->course)->create(['title' => 'Dua', 'sort_order' => 2]);
    $progress = SegmentProgress::factory()->create(['course_segment_id' => $first->id]);

    saveSegments([
        ['id' => $second->id, 'title' => 'Dua', 'url' => 'https://youtu.be/bbbbbbbbbbb'],
        ['id' => $first->id, 'title' => 'Satu diganti', 'url' => 'https://youtu.be/aaaaaaaaaaa'],
    ])->assertSessionHasNoErrors();

    expect($first->fresh())->sort_order->toBe(2)->title->toBe('Satu diganti')->youtube_video_id->toBe('aaaaaaaaaaa')
        ->and($second->fresh()->sort_order)->toBe(1)
        ->and($progress->fresh())->not->toBeNull();
});

it('deletes rows that were removed from the list', function () {
    $kept = CourseSegment::factory()->for($this->course)->create(['sort_order' => 1]);
    $removed = CourseSegment::factory()->for($this->course)->create(['sort_order' => 2]);

    saveSegments([['id' => $kept->id, 'title' => 'Tetap', 'url' => 'https://youtu.be/aaaaaaaaaaa']]);

    expect($removed->fresh())->toBeNull()->and($kept->fresh())->not->toBeNull();
});

it('shows inline errors and saves nothing when one row is wrong', function (array $row, string $key, string $message) {
    $existing = CourseSegment::factory()->for($this->course)->create(['title' => 'Lama', 'sort_order' => 1]);

    saveSegments([
        ['id' => $existing->id, 'title' => 'Baru', 'url' => 'https://youtu.be/aaaaaaaaaaa'],
        $row,
    ])->assertSessionHasErrors([$key => $message]);

    expect($existing->fresh()->title)->toBe('Lama')->and(CourseSegment::query()->count())->toBe(1);
})->with([
    'bad link' => [['title' => 'Dua', 'url' => 'https://vimeo.com/123'], 'segments.1.url', 'Tautan YouTube belum benar.'],
    'empty link' => [['title' => 'Dua', 'url' => ''], 'segments.1.url', 'Tautan YouTube belum benar.'],
    'empty title' => [['title' => '', 'url' => 'https://youtu.be/bbbbbbbbbbb'], 'segments.1.title', 'Judul video wajib diisi.'],
    'seconds over 59' => [['title' => 'Dua', 'url' => 'https://youtu.be/bbbbbbbbbbb', 'seconds' => '75'], 'segments.1.seconds', 'Detik harus angka 0 sampai 59.'],
    'minutes not a number' => [['title' => 'Dua', 'url' => 'https://youtu.be/bbbbbbbbbbb', 'minutes' => 'tiga'], 'segments.1.minutes', 'Menit harus angka bulat.'],
]);

it('refuses a segment id from another course', function () {
    $foreign = CourseSegment::factory()->create(['title' => 'Milik kursus lain']);

    saveSegments([['id' => $foreign->id, 'title' => 'Dibajak', 'url' => 'https://youtu.be/aaaaaaaaaaa']])
        ->assertSessionHasErrors('segments.0.id');

    expect($foreign->fresh()->title)->toBe('Milik kursus lain');
});

it('lets a draft drop every video but keeps at least one on a published course', function () {
    CourseSegment::factory()->for($this->course)->create();

    saveSegments([])->assertSessionHasNoErrors();
    expect($this->course->segments()->count())->toBe(0);

    $this->course->update(['status' => 'published', 'published_at' => now()]);
    CourseSegment::factory()->for($this->course)->create();

    saveSegments([])->assertSessionHas('error', 'Kursus terbit minimal punya satu video. Jadikan draf dulu untuk menghapus semua video.');
    expect($this->course->segments()->count())->toBe(1);
});

it('renders the editor with the saved rows and the PRD help text', function () {
    CourseSegment::factory()->for($this->course)->create(['title' => 'Menyiapkan galon', 'youtube_video_id' => 'dQw4w9WgXcQ', 'duration_seconds' => 200, 'sort_order' => 1]);

    $this->actingAs($this->admin)->get(route('admin.courses.edit', $this->course))
        ->assertSeeText('Satu baris per video. Tempel tautan video YouTube unlisted untuk setiap segmen.')
        ->assertSee('segmentEditor(', false)
        ->assertSee('Menyiapkan galon', false)
        ->assertSee('youtu.be', false)
        ->assertSee('dQw4w9WgXcQ', false)
        ->assertSee('\u0022minutes\u0022:3', false)
        ->assertSeeText('Tambah baris')
        ->assertSeeText('Simpan video');
});

it('brings back the typed rows and their errors after a failed save', function () {
    $html = $this->actingAs($this->admin)->from(route('admin.courses.edit', $this->course))->followingRedirects()
        ->put(route('admin.courses.segments.update', $this->course), ['segments' => [['title' => 'Ketikan saya', 'url' => 'salah']]])
        ->getContent();

    expect($html)->toContain('Ketikan saya')->toContain('Tautan YouTube belum benar.');
});
