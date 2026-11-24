<?php

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\SegmentProgress;
use App\Models\User;
use App\Notifications\AccessGrantedNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Admin Utama', 'email' => 'admin@contoh.id']);
});

function editUser(User $user, array $overrides = [])
{
    return test()->actingAs(test()->admin)->from('/admin/users')->put("/admin/users/{$user->id}", [
        '_modal' => "user-{$user->id}",
        'name' => $user->name,
        'email' => $user->email,
        'phone' => $user->phone,
        'role' => $user->role->value,
        'password' => '',
        'password_confirmation' => '',
        ...$overrides,
    ]);
}

it('lists users with role, verification, courses owned and paid total', function () {
    $buyer = User::factory()->create(['name' => 'Sari Lestari', 'email' => 'sari@contoh.id', 'phone' => '08123456789']);
    User::factory()->unverified()->create(['name' => 'Budi Baru']);
    Enrollment::factory()->count(2)->for($buyer)->create();
    Order::factory()->paid()->for($buyer)->create(['amount' => 150000]);
    Order::factory()->paid()->for($buyer)->create(['amount' => 100000]);
    Order::factory()->for($buyer)->create(['amount' => 999000]);

    $this->actingAs($this->admin)->get('/admin/users')
        ->assertOk()
        ->assertSeeInOrder(['Admin Utama', 'Anda'])
        ->assertSeeText('sari@contoh.id')
        ->assertSeeText('08123456789')
        ->assertSeeText('Rp 250.000')
        ->assertDontSeeText('Rp 1.249.000')
        ->assertSeeText('Belum verifikasi')
        ->assertSeeText('Terverifikasi')
        ->assertSeeText('Admin')
        ->assertSeeText('Pengguna')
        ->assertSee('aria-label="Beri akses kursus"', false)
        ->assertSeeText('Ekspor CSV');
});

it('filters by name or email and by role', function () {
    User::factory()->create(['name' => 'Sari Lestari', 'email' => 'sari@contoh.id']);
    User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@contoh.id']);

    $this->actingAs($this->admin)->get('/admin/users?q=lestari')->assertSeeText('Sari Lestari')->assertDontSeeText('Budi Santoso');
    $this->get('/admin/users?q=budi%40')->assertSeeText('Budi Santoso')->assertDontSeeText('Sari Lestari');
    $this->get('/admin/users?role=admin')->assertSeeText('Admin Utama')->assertDontSeeText('Budi Santoso');
    $this->get('/admin/users?q=tidak-ada')->assertSeeText('Tidak ada pengguna yang cocok dengan pencarian ini.');
});

it('refuses non-admins', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/users')->assertForbidden();
    $this->actingAs($user)->get('/admin/users/export')->assertForbidden();
});

it('edits a user and asks for a new verification when the email changes', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'lama@contoh.id']);

    editUser($user, ['name' => 'Nama Baru', 'email' => 'Baru@Contoh.id', 'phone' => '0812-3456-789'])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'Pengguna disimpan.');

    expect($user->fresh())->name->toBe('Nama Baru')->email->toBe('baru@contoh.id')->phone->toBe('08123456789')->email_verified_at->toBeNull();
    Notification::assertSentTo($user, VerifyEmailNotification::class);
});

it('keeps verification when the email stays the same and allows an empty phone', function () {
    $user = User::factory()->create();

    editUser($user, ['phone' => ''])->assertSessionHasNoErrors();

    expect($user->fresh())->email_verified_at->not->toBeNull()->phone->toBeNull();
});

it('changes the password only when one is typed', function () {
    $user = User::factory()->create();
    $oldHash = $user->password;

    editUser($user)->assertSessionHasNoErrors();
    expect($user->fresh()->password)->toBe($oldHash);

    editUser($user, ['password' => 'rahasiabaru', 'password_confirmation' => 'rahasiabaru'])->assertSessionHasNoErrors();
    expect(Hash::check('rahasiabaru', $user->fresh()->password))->toBeTrue();
});

it('shows the PRD messages inside the edit dialog', function (array $input, string $field, string $message) {
    User::factory()->create(['email' => 'dipakai@contoh.id']);
    $user = User::factory()->create(['name' => 'Sari']);

    $html = $this->actingAs($this->admin)->from('/admin/users')->followingRedirects()->put("/admin/users/{$user->id}", [
        '_modal' => "user-{$user->id}", 'name' => 'Sari', 'email' => $user->email, 'phone' => '', 'role' => 'user',
        'password' => '', 'password_confirmation' => '', ...$input,
    ])->getContent();

    expect($html)->toContain('id="user-'.$user->id.'-'.$field.'-error" role="alert">'.$message);
})->with([
    'email taken' => [['email' => 'dipakai@contoh.id'], 'email', 'Email sudah dipakai pengguna lain.'],
    'bad phone' => [['phone' => '12345'], 'phone', 'Nomor telepon belum benar. Gunakan format 08xx atau 628xx.'],
    'short password' => [['password' => 'pendek', 'password_confirmation' => 'pendek'], 'password', 'Kata sandi minimal 8 karakter.'],
    'unconfirmed password' => [['password' => 'rahasiabaru', 'password_confirmation' => 'lainnya1'], 'password_confirmation', 'Ulangi kata sandi belum sama dengan kata sandi di atas.'],
]);

it('never changes the role of the signed-in admin', function () {
    editUser($this->admin, ['role' => 'user'])->assertSessionHasNoErrors();

    expect($this->admin->fresh()->role)->toBe(UserRole::Admin);
    $this->get('/admin/users')->assertSeeText('Peran akun sendiri tidak bisa diubah.');
});

it('promotes and demotes other users', function () {
    $user = User::factory()->create();

    editUser($user, ['role' => 'admin']);
    expect($user->fresh()->role)->toBe(UserRole::Admin);

    editUser($user->fresh(), ['role' => 'user']);
    expect($user->fresh()->role)->toBe(UserRole::User);
});

it('grants a course with an audit note and emails the user', function () {
    Notification::fake();
    $user = User::factory()->create(['name' => 'Sari']);
    $course = Course::factory()->published()->create(['title' => 'Lele Galon']);

    $this->actingAs($this->admin)->from('/admin/users')->post("/admin/users/{$user->id}/enrollments", [
        '_modal' => "grant-{$user->id}", 'course_id' => $course->id, 'note' => 'Kompensasi kendala pembayaran',
    ])->assertSessionHasNoErrors()->assertSessionHas('status', 'Akses Lele Galon diberikan ke Sari.');

    $enrollment = Enrollment::firstWhere(['user_id' => $user->id, 'course_id' => $course->id]);
    expect($enrollment)->order_id->toBeNull()->granted_by_user_id->toBe($this->admin->id)->note->toBe('Kompensasi kendala pembayaran');

    Notification::assertSentTo($user, AccessGrantedNotification::class, function ($notification) use ($user) {
        $mail = $notification->toMail($user);

        return $mail->subject === 'Akses kursus diberikan'
            && $mail->actionText === 'Mulai belajar'
            && str_contains(implode(' ', $mail->introLines), 'Lele Galon')
            && ! str_contains(serialize($mail->toArray()), 'Kompensasi');
    });
});

it('refuses a second grant and a missing reason', function () {
    $user = User::factory()->create(['name' => 'Sari']);
    $course = Course::factory()->create();
    $other = Course::factory()->create();
    Enrollment::factory()->for($user)->for($course)->create();

    $html = $this->actingAs($this->admin)->from('/admin/users')->followingRedirects()
        ->post("/admin/users/{$user->id}/enrollments", ['_modal' => "grant-{$user->id}", 'course_id' => $course->id, 'note' => 'Lagi'])
        ->getContent();
    expect($html)->toContain('Sari sudah punya akses ke kursus ini.');

    $html = $this->from('/admin/users')->followingRedirects()
        ->post("/admin/users/{$user->id}/enrollments", ['_modal' => "grant-{$user->id}", 'course_id' => $other->id, 'note' => ''])
        ->getContent();
    expect($html)->toContain('id="grant-'.$user->id.'-note-error" role="alert">Alasan wajib diisi.')
        ->and(Enrollment::query()->count())->toBe(1);
});

it('removes personal data but keeps orders', function () {
    Log::spy();
    $user = User::factory()->create(['name' => 'Sari', 'phone' => '08123456789']);
    $enrollment = Enrollment::factory()->for($user)->create();
    SegmentProgress::factory()->for($user)->create();
    $order = Order::factory()->paid()->for($user)->create();
    DB::table('sessions')->insert(['id' => 'sesi-sari', 'user_id' => $user->id, 'payload' => 'x', 'last_activity' => time()]);
    $oldHash = $user->password;

    $this->actingAs($this->admin)->from('/admin/users')->delete("/admin/users/{$user->id}/personal-data")
        ->assertSessionHas('status', 'Data pribadi pengguna dihapus.');

    expect($user->fresh())
        ->name->toBe("Pengguna dihapus #{$user->id}")
        ->email->toBe("deleted-{$user->id}@example.invalid")
        ->phone->toBeNull()
        ->email_verified_at->toBeNull()
        ->password->not->toBe($oldHash)
        ->and($enrollment->fresh())->toBeNull()
        ->and(SegmentProgress::query()->where('user_id', $user->id)->count())->toBe(0)
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0)
        ->and($order->fresh()->user_id)->toBe($user->id);
    Log::shouldHaveReceived('info')->withArgs(fn ($message, $context) => $context['user_id'] === $user->id && $context['admin_id'] === $this->admin->id);
});

it('says when a user already owns every course', function () {
    $user = User::factory()->create(['name' => 'Sari']);
    Enrollment::factory()->for($user)->create();

    $this->actingAs($this->admin)->get('/admin/users')->assertSeeText('Sari sudah punya akses ke semua kursus.');
});

it('refuses to remove the personal data of the signed-in admin', function () {
    $this->actingAs($this->admin)->from('/admin/users')->delete("/admin/users/{$this->admin->id}/personal-data")
        ->assertSessionHas('error', 'Data pribadi akun sendiri tidak bisa dihapus.');

    expect($this->admin->fresh()->email)->toBe('admin@contoh.id');
});

it('shows the anonymize confirmation in the edit dialog', function () {
    User::factory()->create(['name' => 'Sari']);
    Course::factory()->create();

    $this->actingAs($this->admin)->get('/admin/users')
        ->assertSeeText('Data pribadi Sari akan dihapus permanen. Riwayat pesanan tetap disimpan untuk pembukuan.')
        ->assertSeeText('Jika diganti, status email kembali belum terverifikasi.')
        ->assertSeeText('Kosongkan jika kata sandi tidak diganti.')
        ->assertSeeText('Wajib diisi dan tersimpan bersama akses sebagai catatan audit.');
});

it('logs every manual course grant', function () {
    Notification::fake();
    Log::spy();
    $user = User::factory()->create();
    $course = Course::factory()->create();

    $this->actingAs($this->admin)->from('/admin/users')->post("/admin/users/{$user->id}/enrollments", [
        '_modal' => "grant-{$user->id}", 'course_id' => $course->id, 'note' => 'Kompensasi',
    ]);

    Log::shouldHaveReceived('info')->withArgs(fn ($message, $context) => $message === 'Admin granted course access'
        && $context['user_id'] === $user->id && $context['course_id'] === $course->id && $context['admin_id'] === $this->admin->id);
});

it('uses Terapkan for the search and shows Hapus filter only while a filter is active', function () {
    User::factory()->create(['name' => 'Sari Lestari', 'email' => 'sari@contoh.id']);

    $this->actingAs($this->admin)->get('/admin/users')
        ->assertSee('<button type="submit" class="btn">Terapkan</button>', false)
        ->assertDontSeeText('Hapus filter');

    $this->get('/admin/users?q=sari')->assertSeeText('Sari Lestari')->assertSee('>Hapus filter</a>', false);
    $this->get('/admin/users?role=admin')->assertSee('>Hapus filter</a>', false);
});

it('clears password reset tokens that still hold the old email', function () {
    $user = User::factory()->create(['email' => 'sari@contoh.id']);
    DB::table('password_reset_tokens')->insert(['email' => 'sari@contoh.id', 'token' => 'hash', 'created_at' => now()]);

    $this->actingAs($this->admin)->from('/admin/users')->delete("/admin/users/{$user->id}/personal-data");

    expect(DB::table('password_reset_tokens')->where('email', 'sari@contoh.id')->count())->toBe(0);
});
