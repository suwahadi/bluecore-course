<?php

use App\Models\Testimonial;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

function validTestimonial(array $overrides = []): array
{
    return [
        '_modal' => 'testimonial-new',
        'name' => 'Bu Sari',
        'note' => 'Bogor',
        'quote' => 'Galon pertama saya panen dua bulan kemudian.',
        'sort_order' => 1,
        'is_visible' => '1',
        'is_genuine' => '1',
        ...$overrides,
    ];
}

it('lists testimonials in order with a shortened quote and status', function () {
    Testimonial::factory()->create(['name' => 'Pak Budi', 'note' => 'Depok', 'quote' => str_repeat('Lele tumbuh sehat. ', 10), 'sort_order' => 2]);
    Testimonial::factory()->create(['name' => 'Bu Sari', 'sort_order' => 1, 'is_visible' => false]);

    $response = $this->actingAs($this->admin)->get('/admin/testimonials')
        ->assertOk()
        ->assertSeeInOrder(['Bu Sari', 'Disembunyikan', 'Pak Budi', 'Depok', 'Tampil'])
        ->assertSee('aria-label="Ubah testimoni"', false)
        ->assertSee('aria-label="Tampilkan testimoni"', false)
        ->assertSee('placeholder="Kota atau kursus yang dibeli"', false)
        ->assertSeeText('Tulis sesuai ucapan pembeli.')
        ->assertDontSeeText('Maksimal 400 karakter.');

    preg_match_all('#<td class="testimonial-cell">(.*?)</td>#s', $response->getContent(), $cells);
    expect($cells[1])->toContain(Str::limit(str_repeat('Lele tumbuh sehat. ', 10), 80));
});

it('shows an empty state without testimonials', function () {
    $this->actingAs($this->admin)->get('/admin/testimonials')
        ->assertSeeText('Belum ada testimoni. Bagian testimoni di beranda baru tampil setelah ada testimoni yang ditampilkan.');
});

it('refuses non-admins', function () {
    $this->actingAs(User::factory()->create())->post('/admin/testimonials', validTestimonial())->assertForbidden();
});

it('creates a testimonial at the chosen position', function () {
    Testimonial::factory()->create(['name' => 'Pak Budi', 'sort_order' => 1]);

    $this->actingAs($this->admin)->from('/admin/testimonials')->post('/admin/testimonials', validTestimonial())
        ->assertSessionHas('status', 'Testimoni dibuat.');

    expect(Testimonial::query()->ordered()->pluck('name')->all())->toBe(['Bu Sari', 'Pak Budi'])
        ->and(Testimonial::firstWhere('name', 'Bu Sari'))->note->toBe('Bogor')->is_genuine->toBeTrue();
});

it('keeps a testimonial marked as a sample until the box is ticked', function () {
    $this->actingAs($this->admin)->from('/admin/testimonials')->post('/admin/testimonials', validTestimonial(['is_genuine' => '0']))
        ->assertSessionHasNoErrors();

    expect(Testimonial::first()->is_genuine)->toBeFalse();
    $this->actingAs($this->admin)->get('/admin/testimonials')->assertSeeText('Testimoni asli dari pembeli');
});

it('stores an empty note as null', function () {
    $this->actingAs($this->admin)->from('/admin/testimonials')->post('/admin/testimonials', validTestimonial(['note' => '']));

    expect(Testimonial::first()->note)->toBeNull();
});

it('shows the PRD messages inside the dialog that failed', function (array $input, string $field, string $message) {
    $html = $this->actingAs($this->admin)->from('/admin/testimonials')->followingRedirects()
        ->post('/admin/testimonials', validTestimonial($input))->getContent();

    expect($html)->toContain('id="testimonial-new-'.$field.'-error" role="alert">'.$message)
        ->and(Testimonial::query()->count())->toBe(0);
})->with([
    'empty name' => [['name' => ''], 'name', 'Nama pembeli wajib diisi.'],
    'long note' => [['note' => str_repeat('a', 81)], 'note', 'Keterangan maksimal 80 karakter.'],
    'short quote' => [['quote' => 'Bagus.'], 'quote', 'Testimoni wajib diisi, minimal 10 karakter.'],
    'long quote' => [['quote' => str_repeat('a', 401)], 'quote', 'Testimoni maksimal 400 karakter.'],
    'zero order' => [['sort_order' => 0], 'sort_order', 'Urutan harus angka bulat mulai dari 1.'],
]);

it('updates a testimonial and moves it', function () {
    Testimonial::factory()->create(['name' => 'Pak Budi', 'sort_order' => 1]);
    $sari = Testimonial::factory()->create(['name' => 'Bu Sari', 'sort_order' => 2]);

    $this->actingAs($this->admin)->from('/admin/testimonials')
        ->put("/admin/testimonials/{$sari->id}", validTestimonial(['_modal' => "testimonial-{$sari->id}", 'quote' => 'Air galon tetap jernih seminggu.', 'is_visible' => '0']))
        ->assertSessionHas('status', 'Testimoni disimpan.');

    expect($sari->fresh())->quote->toBe('Air galon tetap jernih seminggu.')->is_visible->toBeFalse()->sort_order->toBe(1);

    $this->post("/admin/testimonials/{$sari->id}/move", ['direction' => 'down']);

    expect($sari->fresh()->sort_order)->toBe(2);
});

it('shows and hides a testimonial', function () {
    $sari = Testimonial::factory()->create(['name' => 'Bu Sari']);

    $this->actingAs($this->admin)->from('/admin/testimonials')->post("/admin/testimonials/{$sari->id}/toggle")
        ->assertSessionHas('status', 'Testimoni Bu Sari disembunyikan.');
    $this->post("/admin/testimonials/{$sari->id}/toggle")->assertSessionHas('status', 'Testimoni Bu Sari ditampilkan.');

    expect($sari->fresh()->is_visible)->toBeTrue();
});

it('deletes a testimonial after confirmation', function () {
    $sari = Testimonial::factory()->create(['name' => 'Bu Sari']);

    $this->actingAs($this->admin)->get('/admin/testimonials')->assertSeeText('Testimoni dari Bu Sari akan dihapus permanen.');
    $this->from('/admin/testimonials')->delete("/admin/testimonials/{$sari->id}")->assertSessionHas('status', 'Testimoni dihapus.');

    expect($sari->fresh())->toBeNull();
});
