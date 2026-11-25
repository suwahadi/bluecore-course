<?php

use App\Models\User;

dataset('hidden characters', [
    'line break' => ["Sari\nLestari"],
    'blank line with a link' => ["Sari\n\n[Konfirmasi](https://jahat.test)"],
    'tab' => ["Sari\tLestari"],
    'null byte' => ["Sari\0Lestari"],
    'right-to-left override' => ["Sari \u{202E}iratseL"],
    'zero-width space' => ["Sari\u{200B}Lestari"],
    'line separator' => ["Sari\u{2028}Lestari"],
    'paragraph separator' => ["Sari\u{2029}Lestari"],
    'byte order mark' => ["Sari\u{FEFF}Lestari"],
    'word joiner' => ["Sari\u{2060}Lestari"],
    'soft hyphen' => ["Sari\u{00AD}Lestari"],
]);

it('refuses hidden characters in the name on sign up', function (string $name) {
    $this->post('/register', [
        'name' => $name, 'phone' => '08123456789', 'email' => 'sari@contoh.id',
        'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
    ])->assertSessionHasErrors(['name' => 'Nama hanya boleh berisi satu baris teks biasa.']);

    expect(User::query()->count())->toBe(0);
})->with('hidden characters');

it('refuses hidden characters in the name on the account page', function (string $name) {
    $user = User::factory()->create(['name' => 'Sari Lestari']);

    $this->actingAs($user)->put('/user/profile-information', ['name' => $name, 'email' => $user->email, 'phone' => '08123456789'])
        ->assertSessionHasErrorsIn('updateProfileInformation', ['name' => 'Nama hanya boleh berisi satu baris teks biasa.']);

    expect($user->fresh()->name)->toBe('Sari Lestari');
})->with('hidden characters');

it('refuses hidden characters in the name in the admin user dialog', function (string $name) {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create(['name' => 'Sari Lestari']);

    $this->actingAs($admin)->from('/admin/users')->put("/admin/users/{$user->id}", [
        '_modal' => "user-{$user->id}", 'name' => $name, 'email' => $user->email, 'phone' => '',
        'role' => 'user', 'password' => '', 'password_confirmation' => '',
    ])->assertSessionHasErrorsIn("user-{$user->id}", ['name' => 'Nama hanya boleh berisi satu baris teks biasa.']);

    expect($user->fresh()->name)->toBe('Sari Lestari');
})->with('hidden characters');

it('keeps ordinary names with accents, apostrophes and emoji', function (string $name) {
    $this->post('/register', [
        'name' => $name, 'phone' => '08123456789', 'email' => 'sari@contoh.id',
        'password' => 'rahasia123', 'password_confirmation' => 'rahasia123',
    ])->assertSessionHasNoErrors();

    expect(User::query()->sole()->name)->toBe($name);
})->with(["Siti Nur'aini", 'José Ramírez', 'Ayu Lestari 🌸', 'I Gusti Ngurah Rai']);
