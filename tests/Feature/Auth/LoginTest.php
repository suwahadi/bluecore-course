<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(fn () => RateLimiter::clear('budi@contoh.id|127.0.0.1'));

it('logs in and goes to next', function () {
    User::factory()->create(['email' => 'budi@contoh.id']);

    $this->post('/login', ['email' => 'budi@contoh.id', 'password' => 'password', 'next' => '/courses/lele/checkout'])
        ->assertRedirect('/courses/lele/checkout');
    $this->assertAuthenticated();
});

it('sends admins to the admin panel and users home without next', function () {
    User::factory()->admin()->create(['email' => 'admin@contoh.id']);
    User::factory()->create(['email' => 'budi@contoh.id']);

    $this->post('/login', ['email' => 'admin@contoh.id', 'password' => 'password'])->assertRedirect('/admin');
    $this->post('/logout');
    $this->post('/login', ['email' => 'budi@contoh.id', 'password' => 'password'])->assertRedirect('/');
});

it('ignores an outside next on login', function () {
    User::factory()->create(['email' => 'budi@contoh.id']);

    $this->post('/login', ['email' => 'budi@contoh.id', 'password' => 'password', 'next' => '//evil.com'])->assertRedirect('/');
});

it('accepts the email in any letter case', function () {
    User::factory()->create(['email' => 'budi@contoh.id']);

    $this->post('/login', ['email' => 'BUDI@Contoh.id', 'password' => 'password'])->assertRedirect('/');
    $this->assertAuthenticated();
});

it('shows one message for a wrong email or password', function () {
    User::factory()->create(['email' => 'budi@contoh.id']);

    $this->post('/login', ['email' => 'budi@contoh.id', 'password' => 'salah-sekali'])
        ->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
    $this->post('/login', ['email' => 'tidak-ada@contoh.id', 'password' => 'password'])
        ->assertSessionHasErrors(['email' => 'Email atau kata sandi salah.']);
    $this->assertGuest();
});

it('locks login after five failures and says how long to wait', function () {
    User::factory()->create(['email' => 'budi@contoh.id']);

    foreach (range(1, 5) as $attempt) {
        $this->post('/login', ['email' => 'budi@contoh.id', 'password' => 'salah-sekali']);
    }

    // Fortify menghitung kegagalan per email|ip dan menolak dengan pesan inline, bukan halaman 429.
    $this->post('/login', ['email' => 'budi@contoh.id', 'password' => 'password'])->assertSessionHasErrors('email');

    expect(session('errors')->first('email'))->toStartWith('Terlalu banyak percobaan. Coba lagi dalam')
        ->toEndWith('detik.');
    $this->assertGuest();
});

it('renders the login form with remember me ticked and links that carry next', function () {
    $this->get('/login?next=/free-video')
        ->assertOk()
        ->assertSeeText('Masuk')
        ->assertSee('name="remember"', false)
        ->assertSee('checked', false)
        ->assertSee('href="'.url('/forgot-password').'"', false)
        ->assertSeeText('Lupa kata sandi?')
        ->assertSeeText('Belum punya akun?')
        ->assertSee('href="'.url('/register').'?next=%2Ffree-video"', false)
        ->assertSee('name="next" value="/free-video"', false);
});

it('logs out with POST and returns home', function () {
    $this->actingAs(User::factory()->create())->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});

it('keeps remember me unticked after a failed login without it', function () {
    User::factory()->create(['email' => 'budi@contoh.id']);

    $this->from('/login')->post('/login', ['email' => 'budi@contoh.id', 'password' => 'salah-sekali']);

    $this->get('/login')->assertDontSee('name="remember" value="1" checked', false);
});

it('keeps remember me ticked after a failed login with it', function () {
    User::factory()->create(['email' => 'budi@contoh.id']);

    $this->from('/login')->post('/login', ['email' => 'budi@contoh.id', 'password' => 'salah-sekali', 'remember' => '1']);

    $this->get('/login')->assertSee('name="remember" value="1" checked', false);
});

it('ignores every outside next on login and registration', function (string $next) {
    User::factory()->create(['email' => 'budi@contoh.id']);

    $this->post('/login', ['email' => 'budi@contoh.id', 'password' => 'password', 'next' => $next])->assertRedirect('/');
    $this->post('/logout');
    $this->post('/register', [
        'name' => 'Sari Lestari', 'phone' => '08123456789', 'email' => 'sari@contoh.id',
        'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'next' => $next,
    ])->assertRedirect('/');
})->with(['https://contoh.com', '//contoh.com', '/\contoh.com', 'javascript:alert(1)']);
