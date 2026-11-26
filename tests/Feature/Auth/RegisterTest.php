<?php

use App\Enums\SignupSource;
use App\Models\Page;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;

function registrationInput(array $overrides = []): array
{
    return array_merge([
        'name' => 'Budi Santoso',
        'phone' => '+62 812-3456-789',
        'email' => 'budi@contoh.id',
        'password' => 'rahasia123',
        'password_confirmation' => 'rahasia123',
    ], $overrides);
}

it('registers a user, cleans the phone, logs in and fires the verification event', function () {
    Event::fake([Registered::class]);

    $this->post('/register', registrationInput(['role' => 'admin', 'next' => '/courses/lele/checkout']))
        ->assertRedirect('/courses/lele/checkout');

    $user = User::firstWhere('email', 'budi@contoh.id');

    expect($user->phone)->toBe('+628123456789')
        ->and($user->isAdmin())->toBeFalse()
        ->and($user->signup_source)->toBe(SignupSource::Course)
        ->and($user->email_verified_at)->toBeNull();
    $this->assertAuthenticatedAs($user);
    Event::assertDispatched(Registered::class);
});

it('derives the signup source from next', function (?string $next, SignupSource $source) {
    $this->post('/register', registrationInput(['next' => $next]));

    expect(User::firstWhere('email', 'budi@contoh.id')->signup_source)->toBe($source);
})->with([
    'free video' => ['/free-video', SignupSource::FreeVideo],
    'course page' => ['/courses/lele', SignupSource::Course],
    'orders' => ['/orders', SignupSource::Direct],
    'outside url' => ['https://evil.com/free-video', SignupSource::Direct],
    'protocol relative' => ['//evil.com/courses/x', SignupSource::Direct],
    'missing' => [null, SignupSource::Direct],
]);

it('ignores an outside next and lands on the home page', function () {
    $this->post('/register', registrationInput(['next' => '//evil.com']))->assertRedirect('/');
});

it('shows the Indonesian validation messages', function () {
    $this->post('/register', ['phone' => '0712', 'email' => 'salah', 'password' => 'pendek', 'password_confirmation' => 'beda'])
        ->assertSessionHasErrors([
            'name' => 'Nama wajib diisi.',
            'phone' => 'Nomor telepon belum benar. Gunakan format 08xx atau 628xx.',
            'email' => 'Format email belum benar, contoh nama@domain.com.',
            'password' => 'Kata sandi minimal 8 karakter.',
            'password_confirmation' => 'Ulangi kata sandi belum sama dengan kata sandi di atas.',
        ]);
});

it('rejects a taken email and an empty phone', function () {
    User::factory()->create(['email' => 'budi@contoh.id']);

    $this->post('/register', registrationInput(['phone' => ' ']))
        ->assertSessionHasErrors([
            'email' => 'Email sudah terdaftar. Masuk dengan email itu.',
            'phone' => 'Nomor telepon wajib diisi.',
        ]);
});

it('treats the email case-insensitively', function () {
    User::factory()->create(['email' => 'budi@contoh.id']);

    $this->post('/register', registrationInput(['email' => 'BUDI@contoh.id']))
        ->assertSessionHasErrors(['email' => 'Email sudah terdaftar. Masuk dengan email itu.']);
});

it('renders the two-column form with helper texts and consent links', function () {
    Page::factory()->create(['title' => 'Syarat dan Ketentuan', 'slug' => 'terms-and-condition', 'system_key' => 'terms']);
    Page::factory()->create(['title' => 'Kebijakan Privasi', 'slug' => 'privacy-policy', 'system_key' => 'privacy', 'is_published' => false]);

    $this->get('/register?next=/free-video')
        ->assertOk()
        ->assertSeeText('Buat akun')
        ->assertSee('class="form-grid form-grid--2"', false)
        ->assertDontSeeText('Format 08xx atau 628xx.')
        ->assertDontSeeText('Minimal 8 karakter.')
        ->assertSee('href="'.url('/terms-and-condition').'"', false)
        ->assertDontSee('href="'.url('/privacy-policy').'"', false)
        ->assertSeeText('Kebijakan Privasi')
        ->assertSee('name="next" value="/free-video"', false)
        ->assertSee('href="'.url('/login').'?next=%2Ffree-video"', false);
});

it('drops an outside next from the form', function () {
    $this->get('/register?next=https://evil.com')->assertOk()->assertDontSee('evil.com', false);
});

it('sends signed-in users away from the register page', function () {
    $this->actingAs(User::factory()->create())->get('/register')->assertRedirect();
});

it('survives array values where strings are expected', function () {
    $this->get('/register?next[]=x')->assertOk();
    $this->get('/login?next[]=x')->assertOk();

    $this->post('/register', ['name' => 'Budi', 'phone' => ['x'], 'email' => ['x'], 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'next' => ['x']])
        ->assertSessionHasErrors(['phone', 'email']);

    User::factory()->create(['email' => 'ada@contoh.id']);
    $this->post('/login', ['email' => 'ada@contoh.id', 'password' => 'password', 'next' => ['x']])->assertRedirect('/');
});

it('accepts 628 numbers on sign up', function () {
    $this->post('/register', registrationInput(['phone' => '628123456789']))->assertSessionHasNoErrors();

    expect(User::firstWhere('email', 'budi@contoh.id')->phone)->toBe('628123456789');
});
