<?php

use App\Models\Category;
use App\Models\Course;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

function categoryNames(): array
{
    return Category::query()->ordered()->pluck('name')->all();
}

it('lists categories in order with course counts and status', function () {
    $lele = Category::factory()->create(['name' => 'Lele', 'slug' => 'lele', 'sort_order' => 2]);
    Category::factory()->create(['name' => 'Nila', 'slug' => 'nila', 'sort_order' => 1, 'is_active' => false]);
    Course::factory()->count(3)->for($lele)->create();

    $this->actingAs($this->admin)->get('/admin/categories')
        ->assertOk()
        ->assertSeeInOrder(['Nila', 'Nonaktif', 'Lele', '3', 'Aktif'])
        ->assertSee('aria-label="Ubah kategori"', false)
        ->assertSeeText('Kategori baru');
});

it('shows an empty state without categories', function () {
    $this->actingAs($this->admin)->get('/admin/categories')->assertSeeText('Belum ada kategori. Buat kategori pertama.');
});

it('refuses non-admins', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/categories')->assertForbidden();
    $this->actingAs($user)->post('/admin/categories', ['name' => 'Lele'])->assertForbidden();
});

it('creates a category at the chosen position with a normalized slug', function () {
    Category::factory()->create(['name' => 'Nila', 'sort_order' => 1]);
    Category::factory()->create(['name' => 'Gurame', 'sort_order' => 2]);

    $this->actingAs($this->admin)->from('/admin/categories')->post('/admin/categories', [
        '_modal' => 'category-new', 'name' => 'Ikan Lele', 'slug' => 'Ikan Lele!', 'sort_order' => 1, 'is_active' => '1',
    ])->assertRedirect('/admin/categories')->assertSessionHas('status', 'Kategori dibuat.');

    expect(categoryNames())->toBe(['Ikan Lele', 'Nila', 'Gurame'])
        ->and(Category::firstWhere('name', 'Ikan Lele')->slug)->toBe('ikan-lele');
});

it('shows the PRD messages inside the dialog that failed', function (array $input, string $field, string $message) {
    Category::factory()->create(['name' => 'Lele', 'slug' => 'lele']);

    $html = $this->actingAs($this->admin)->from('/admin/categories')->followingRedirects()
        ->post('/admin/categories', [...['_modal' => 'category-new', 'name' => 'Nila', 'slug' => 'nila', 'sort_order' => 1, 'is_active' => '1'], ...$input])
        ->getContent();

    expect($html)->toContain('id="category-new-'.$field.'-error" role="alert">'.$message)
        ->toContain('x-data="{ open: true }"')
        ->and(Category::query()->count())->toBe(1);
})->with([
    'empty name' => [['name' => ''], 'name', 'Nama kategori wajib diisi, minimal 2 karakter.'],
    'short name' => [['name' => 'N'], 'name', 'Nama kategori wajib diisi, minimal 2 karakter.'],
    'name taken in other case' => [['name' => 'LELE'], 'name', 'Nama kategori sudah dipakai.'],
    'zero order' => [['sort_order' => 0], 'sort_order', 'Urutan harus angka bulat mulai dari 1.'],
    'decimal order' => [['sort_order' => '1.5'], 'sort_order', 'Urutan harus angka bulat mulai dari 1.'],
    'empty slug' => [['slug' => '!!!'], 'slug', 'Slug wajib diisi.'],
    'slug taken' => [['slug' => 'Lele'], 'slug', 'Slug sudah dipakai.'],
]);

it('updates a category without tripping over its own name and slug', function () {
    Category::factory()->create(['name' => 'Nila', 'sort_order' => 1]);
    $lele = Category::factory()->create(['name' => 'Lele', 'slug' => 'lele', 'sort_order' => 2]);

    $this->actingAs($this->admin)->from('/admin/categories')->put("/admin/categories/{$lele->id}", [
        '_modal' => "category-{$lele->id}", 'name' => 'Lele', 'slug' => 'lele-dumbo', 'sort_order' => 1, 'is_active' => '0',
    ])->assertSessionHasNoErrors()->assertSessionHas('status', 'Kategori disimpan.');

    expect(categoryNames())->toBe(['Lele', 'Nila'])
        ->and($lele->fresh())->slug->toBe('lele-dumbo')->is_active->toBeFalse();
});

it('moves categories up and down', function () {
    Category::factory()->create(['name' => 'Nila', 'sort_order' => 1]);
    $lele = Category::factory()->create(['name' => 'Lele', 'sort_order' => 2]);

    $this->actingAs($this->admin)->from('/admin/categories')
        ->post("/admin/categories/{$lele->id}/move", ['direction' => 'up'])->assertRedirect('/admin/categories');

    expect(categoryNames())->toBe(['Lele', 'Nila']);

    $this->post("/admin/categories/{$lele->id}/move", ['direction' => 'sideways'])->assertSessionHasErrors('direction');
});

it('toggles a category on and off', function () {
    $lele = Category::factory()->create(['name' => 'Lele']);

    $this->actingAs($this->admin)->from('/admin/categories')->post("/admin/categories/{$lele->id}/toggle")
        ->assertSessionHas('status', 'Kategori Lele dinonaktifkan.');
    expect($lele->fresh()->is_active)->toBeFalse();

    $this->post("/admin/categories/{$lele->id}/toggle")->assertSessionHas('status', 'Kategori Lele diaktifkan.');
    expect($lele->fresh()->is_active)->toBeTrue();
});

it('refuses to delete a category that courses still use', function () {
    $lele = Category::factory()->create();
    Course::factory()->for($lele)->create();

    $this->actingAs($this->admin)->from('/admin/categories')->delete("/admin/categories/{$lele->id}")
        ->assertRedirect('/admin/categories')
        ->assertSessionHas('error', 'Kategori masih dipakai kursus. Pindahkan kursusnya dulu.');

    expect($lele->fresh())->not->toBeNull();
});

it('deletes an unused category', function () {
    $lele = Category::factory()->create();

    $this->actingAs($this->admin)->from('/admin/categories')->delete("/admin/categories/{$lele->id}")
        ->assertSessionHas('status', 'Kategori dihapus.');

    expect($lele->fresh())->toBeNull();
});

it('pages the list by 20 with Indonesian pager labels', function () {
    Category::factory()->count(21)->sequence(fn ($sequence) => ['name' => 'Kategori '.str_pad($sequence->index, 2, '0', STR_PAD_LEFT), 'sort_order' => $sequence->index + 1])->create();

    $this->actingAs($this->admin)->get('/admin/categories')
        ->assertSeeText('Halaman 1 dari 2')
        ->assertSeeText('Berikutnya')
        ->assertDontSeeText('Kategori 20');
});
