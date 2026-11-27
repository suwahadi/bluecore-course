<?php

use App\Models\Page;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

function validPage(array $overrides = []): array
{
    return [
        '_modal' => 'page-new',
        'title' => 'Tentang Kami',
        'slug' => 'tentang-kami',
        'content' => "## Siapa kami\n\nBlueCore di Bogor.",
        'is_published' => '1',
        'sort_order' => 1,
        'show_in_footer' => '1',
        'requires_signup' => '1',
        ...$overrides,
    ];
}

it('lists pages with their link, status, footer flag and system label', function () {
    Page::factory()->create(['title' => 'Syarat dan Ketentuan', 'slug' => 'terms-and-condition', 'system_key' => 'terms', 'sort_order' => 1]);
    Page::factory()->create(['title' => 'Tentang Kami', 'slug' => 'tentang-kami', 'is_published' => false, 'show_in_footer' => false, 'sort_order' => 2]);

    $this->actingAs($this->admin)->get('/admin/pages')
        ->assertOk()
        ->assertSeeInOrder(['Syarat dan Ketentuan', '/terms-and-condition', 'halaman sistem', 'Terbit', 'Tampil', 'Tentang Kami', '/tentang-kami', 'Draf', 'Tidak'])
        ->assertSee('aria-label="Halaman sistem tidak bisa dihapus"', false)
        ->assertSee('aria-label="Terbitkan halaman"', false)
        ->assertSeeText('Markdown: baris "## Judul" untuk judul, "- " untuk daftar.')
        ->assertSee('placeholder="Misalnya Tentang Kami"', false);
});

it('shows an empty state without pages', function () {
    $this->actingAs($this->admin)->get('/admin/pages')->assertSeeText('Belum ada halaman. Buat halaman pertama, misalnya Tentang Kami.');
});

it('creates a page and shows it in the footer', function () {
    $this->actingAs($this->admin)->get('/admin/pages')->assertDontSee('href="'.url('/tentang-kami').'"', false);

    $this->from('/admin/pages')->post('/admin/pages', validPage())->assertSessionHas('status', 'Halaman dibuat.');

    $page = Page::firstWhere('slug', 'tentang-kami');
    expect($page)->is_published->toBeTrue()->show_in_footer->toBeTrue()->system_key->toBeNull();
    $this->get('/admin/pages')->assertSee('href="'.url('/tentang-kami').'"', false);
});

it('keeps footer unticked when the box is cleared', function () {
    $this->actingAs($this->admin)->from('/admin/pages')->post('/admin/pages', validPage(['show_in_footer' => '0']));

    expect(Page::first()->show_in_footer)->toBeFalse();
});

it('shows the PRD messages inside the dialog that failed', function (array $input, string $field, string $message) {
    Page::factory()->create(['slug' => 'profil']);

    $html = $this->actingAs($this->admin)->from('/admin/pages')->followingRedirects()
        ->post('/admin/pages', validPage($input))->getContent();

    expect($html)->toContain('id="page-new-'.$field.'-error" role="alert">'.$message)
        ->and(Page::query()->count())->toBe(1);
})->with([
    'short title' => [['title' => 'A'], 'title', 'Judul wajib diisi, minimal 2 karakter.'],
    'system slug' => [['slug' => 'Admin'], 'slug', 'Slug ini dipakai sistem. Pilih slug lain.'],
    'fonts folder slug' => [['slug' => 'fonts'], 'slug', 'Slug ini dipakai sistem. Pilih slug lain.'],
    'livewire prefix slug' => [['slug' => 'livewire-panduan'], 'slug', 'Slug ini dipakai sistem. Pilih slug lain.'],
    'slug taken' => [['slug' => 'profil'], 'slug', 'Slug sudah dipakai.'],
    'zero order' => [['sort_order' => 0], 'sort_order', 'Urutan harus angka bulat mulai dari 1.'],
]);

it('updates a system page but never its system key', function () {
    $terms = Page::factory()->create(['title' => 'Syarat dan Ketentuan', 'slug' => 'terms-and-condition', 'system_key' => 'terms']);

    $this->actingAs($this->admin)->from('/admin/pages')
        ->put("/admin/pages/{$terms->id}", validPage(['_modal' => "page-{$terms->id}", 'title' => 'Syarat Layanan', 'slug' => 'syarat', 'system_key' => null]))
        ->assertSessionHas('status', 'Halaman disimpan.');

    expect($terms->fresh())->title->toBe('Syarat Layanan')->slug->toBe('syarat')->system_key->toBe('terms');
});

it('refuses to delete a system page even through a crafted request', function () {
    $terms = Page::factory()->create(['system_key' => 'terms']);

    $this->actingAs($this->admin)->from('/admin/pages')->delete("/admin/pages/{$terms->id}")
        ->assertSessionHas('error', 'Halaman sistem tidak bisa dihapus.');

    expect($terms->fresh())->not->toBeNull();
});

it('deletes an ordinary page', function () {
    $page = Page::factory()->create(['title' => 'Tentang Kami']);

    $this->actingAs($this->admin)->get('/admin/pages')->assertSeeText('Halaman Tentang Kami akan dihapus permanen.');
    $this->from('/admin/pages')->delete("/admin/pages/{$page->id}")->assertSessionHas('status', 'Halaman dihapus.');

    expect($page->fresh())->toBeNull();
});

it('publishes and unpublishes with a toast', function () {
    $page = Page::factory()->create(['title' => 'Tentang Kami', 'is_published' => false]);

    $this->actingAs($this->admin)->from('/admin/pages')->post("/admin/pages/{$page->id}/toggle")
        ->assertSessionHas('status', 'Halaman Tentang Kami diterbitkan.');
    $this->post("/admin/pages/{$page->id}/toggle")->assertSessionHas('status', 'Halaman Tentang Kami dijadikan draf.');
});

it('refreshes the footer after save, toggle and move', function () {
    $about = Page::factory()->create(['title' => 'Tentang Kami', 'sort_order' => 1]);
    Page::factory()->create(['title' => 'Cara Belajar', 'sort_order' => 2]);
    $this->actingAs($this->admin)->get('/admin/pages');

    $this->from('/admin/pages')->put("/admin/pages/{$about->id}", validPage(['_modal' => "page-{$about->id}", 'title' => 'Profil BlueCore', 'slug' => $about->slug]));
    $this->get('/admin/pages')->assertSee('>Profil BlueCore</a>', false);

    $this->post("/admin/pages/{$about->id}/move", ['direction' => 'down']);
    $footer = $this->get('/admin/pages')->getContent();
    expect(strpos($footer, '>Cara Belajar</a>'))->toBeLessThan(strpos($footer, '>Profil BlueCore</a>'));

    $this->post("/admin/pages/{$about->id}/toggle");
    $this->get('/admin/pages')->assertDontSee('>Profil BlueCore</a>', false);
});

it('keeps the footer cache when the save rolls back', function () {
    Illuminate\Support\Facades\Cache::forever(Page::FOOTER_CACHE_KEY, 'cached');

    try {
        Illuminate\Support\Facades\DB::transaction(function () {
            Page::factory()->create();

            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }

    expect(Illuminate\Support\Facades\Cache::get(Page::FOOTER_CACHE_KEY))->toBe('cached');
});

it('accepts a slug that only contains livewire later on', function () {
    $this->actingAs($this->admin)->from('/admin/pages')->post('/admin/pages', validPage(['slug' => 'panduan-livewire']))
        ->assertSessionHas('status', 'Halaman dibuat.');

    $this->get('/panduan-livewire')->assertOk();
});

it('saves a landing video with an open gate', function () {
    $this->actingAs($this->admin)->post('/admin/pages', validPage(['youtube_url' => 'https://youtu.be/M7lc1UVf-VE', 'requires_signup' => '0']))
        ->assertSessionHasNoErrors();

    $page = Page::firstWhere('slug', 'tentang-kami');
    expect($page->youtube_url)->toBe('https://youtu.be/M7lc1UVf-VE')->and($page->requires_signup)->toBeFalse();
});

it('refuses a landing link that is not a YouTube video over https', function (string $url) {
    $this->actingAs($this->admin)->from('/admin/pages')->post('/admin/pages', validPage(['youtube_url' => $url]))
        ->assertSessionHasErrorsIn('page-new', ['youtube_url' => 'Tautan YouTube harus tautan video YouTube yang diawali https://.']);
})->with(['vimeo' => 'https://vimeo.com/1', 'plain http' => 'http://youtu.be/M7lc1UVf-VE']);

it('shows the landing fields and marks landing pages in the list', function () {
    Page::factory()->create(['title' => 'Lele Pemula', 'slug' => 'lele-pemula', 'youtube_url' => 'https://youtu.be/M7lc1UVf-VE']);
    Page::factory()->create(['title' => 'Tentang Kami', 'slug' => 'tentang-kami']);

    $html = $this->actingAs($this->admin)->get('/admin/pages')->assertOk()->getContent();

    expect($html)->toContain('name="youtube_url"')
        ->toContain('Wajib daftar untuk menonton')
        ->toContain('Isi untuk menjadikan halaman ini landing video.')
        ->and(substr_count($html, 'landing video</div>'))->toBe(1);
});

