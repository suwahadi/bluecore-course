<?php

use App\Models\Course;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = User::factory()->admin()->create();
});

function settingsInput(array $overrides = []): array
{
    return [...collect(Settings::all())->except('logo')->map(fn ($value) => $value ?? '')->all(), ...$overrides];
}

function saveSettings(array $overrides = [])
{
    return test()->actingAs(test()->admin)->from('/admin/settings')->put('/admin/settings', settingsInput($overrides));
}

function logoUpload(int $width = 900, int $height = 300): UploadedFile
{
    $image = imagecreatetruecolor($width, $height);
    $path = tempnam(sys_get_temp_dir(), 'logo');
    imagepng($image, $path);

    return new UploadedFile($path, 'logo.png', 'image/png', null, true);
}

it('shows every panel with the PRD labels and help texts', function () {
    $this->actingAs($this->admin)->get('/admin/settings')
        ->assertOk()
        ->assertSeeInOrder(['Identitas', 'Kontak', 'Sosial', 'Marketplace', 'Pembayaran', 'Beranda', 'Video gratis', 'Pelacakan'])
        ->assertSeeText('Slogan (di bawah nama situs pada logo dan judul beranda)')
        ->assertSeeText('Deskripsi singkat (beranda, footer, meta)')
        ->assertDontSeeText('Format internasional tanpa tanda plus, contoh 628123456789.')
        ->assertSeeText('Tautan toko untuk ikon Marketplace di footer. Kosongkan jika belum punya toko di marketplace tersebut.')
        ->assertSeeText('Biaya admin per pesanan (rupiah)')
        ->assertSeeText('Ditambahkan ke setiap pesanan kursus berbayar.')
        ->assertSeeText('Kursus ini tampil di hero beranda dan menjadi tujuan tombol Lihat kursus.')
        ->assertSeeText('Otomatis: kursus terbit terbaru')
        ->assertSeeText('Tampil di halaman Nonton gratis untuk pengguna yang sudah daftar. Kosongkan tautan untuk menyembunyikan tombol Nonton gratis di beranda.')
        ->assertSeeText('Tampil di bawah judul video. Pisahkan paragraf dengan satu baris kosong.')
        ->assertSee('placeholder="G-XXXXXXXXXX"', false)
        ->assertSee('value="BlueCore Course"', false)
        ->assertSeeText('Simpan pengaturan');
});

it('refuses non-admins', function () {
    $this->actingAs(User::factory()->create())->put('/admin/settings', settingsInput())->assertForbidden();
});

it('saves the settings and the header uses them right away', function () {
    $course = Course::factory()->published()->create();

    saveSettings(['site_name' => 'Kolam Galon', 'admin_fee' => '2500', 'featured_course_id' => $course->id, 'shopee_url' => ''])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'Pengaturan disimpan.');

    expect(Settings::get('site_name'))->toBe('Kolam Galon')
        ->and(Settings::get('admin_fee'))->toBe(2500)
        ->and(Settings::get('featured_course_id'))->toBe($course->id)
        ->and(Settings::get('shopee_url'))->toBeNull();
    $this->get('/admin/settings')->assertSee('Kolam Galon');
});

it('shows the PRD messages inline', function (array $input, string $field, string $message) {
    saveSettings($input)->assertSessionHasErrors([$field => $message]);
})->with([
    'empty site name' => [['site_name' => ''], 'site_name', 'Nama situs wajib diisi.'],
    'whatsapp with plus' => [['whatsapp' => '+628123456789'], 'whatsapp', 'Nomor WhatsApp harus diawali 62 tanpa tanda plus.'],
    'bad email' => [['email' => 'bukan-email'], 'email', 'Format email belum benar, contoh nama@domain.com.'],
    'instagram over http' => [['instagram_url' => 'http://instagram.com/x'], 'instagram_url', 'Tautan Instagram harus diawali https://.'],
    'tiktok shop over http' => [['tiktokshop_url' => 'http://shop.tiktok.com/x'], 'tiktokshop_url', 'Tautan TikTok Shop harus diawali https://.'],
    'negative fee' => [['admin_fee' => '-1'], 'admin_fee', 'Biaya admin harus angka bulat 0 sampai 1.000.000.'],
    'fee too high' => [['admin_fee' => '1000001'], 'admin_fee', 'Biaya admin harus angka bulat 0 sampai 1.000.000.'],
    'lead magnet not youtube' => [['lead_magnet_youtube_url' => 'https://vimeo.com/1', 'lead_magnet_title' => 'Gratis'], 'lead_magnet_youtube_url', 'Tautan video gratis harus tautan YouTube yang diawali https://.'],
    'lead magnet over http' => [['lead_magnet_youtube_url' => 'http://youtu.be/dQw4w9WgXcQ', 'lead_magnet_title' => 'Gratis'], 'lead_magnet_youtube_url', 'Tautan video gratis harus tautan YouTube yang diawali https://.'],
    'lead magnet without title' => [['lead_magnet_youtube_url' => 'https://youtu.be/dQw4w9WgXcQ', 'lead_magnet_title' => ''], 'lead_magnet_title', 'Judul video gratis wajib diisi.'],
    'ga4 without prefix' => [['ga4_measurement_id' => 'UA-12345'], 'ga4_measurement_id', 'ID Google Analytics harus diawali G-.'],
    'pixel with letters' => [['meta_pixel_id' => 'abc123'], 'meta_pixel_id', 'ID Meta Pixel hanya berisi angka.'],
]);

it('only features a published course', function () {
    $draft = Course::factory()->create();

    saveSettings(['featured_course_id' => $draft->id])->assertSessionHasErrors(['featured_course_id' => 'Pilih kursus yang sudah terbit.']);
});

it('stores a new logo as a 400 pixel WebP and removes the old one after saving', function () {
    Storage::disk('public')->put('site/lama.webp', 'x');
    Settings::set(['logo' => 'site/lama.webp']);

    saveSettings(['logo_upload' => logoUpload()])->assertSessionHasNoErrors();

    $logo = Settings::get('logo');
    expect($logo)->toMatch('#^site/[a-z0-9]{14}\.webp$#')
        ->and(getimagesizefromstring(Storage::disk('public')->get($logo))[0])->toBe(400);
    Storage::disk('public')->assertMissing('site/lama.webp');
});

it('removes the logo when asked', function () {
    Storage::disk('public')->put('site/lama.webp', 'x');
    Settings::set(['logo' => 'site/lama.webp']);

    $this->actingAs($this->admin)->get('/admin/settings')->assertSeeText('Hapus logo');
    saveSettings(['remove_logo' => '1'])->assertSessionHasNoErrors();

    expect(Settings::get('logo'))->toBeNull();
    Storage::disk('public')->assertMissing('site/lama.webp');
});

it('keeps the old logo and stores nothing new when validation fails', function () {
    Storage::disk('public')->put('site/lama.webp', 'x');
    Settings::set(['logo' => 'site/lama.webp']);

    saveSettings(['site_name' => '', 'logo_upload' => logoUpload()]);

    expect(Settings::get('logo'))->toBe('site/lama.webp')
        ->and(Storage::disk('public')->allFiles())->toBe(['site/lama.webp']);
});

it('logs who changed which keys without their values', function () {
    Log::spy();

    saveSettings(['site_name' => 'Kolam Galon', 'meta_pixel_id' => '1234567890']);

    Log::shouldHaveReceived('info')->withArgs(fn ($message, $context) => $message === 'Admin updated settings'
        && $context['admin_id'] === $this->admin->id
        && $context['keys'] === ['site_name', 'meta_pixel_id']
        && ! str_contains(json_encode($context), 'Kolam Galon'));
});

it('clears the settings cache only after the transaction commits', function () {
    Settings::get('site_name');
    expect(Cache::has('settings'))->toBeTrue();

    try {
        DB::transaction(function () {
            Settings::set(['site_name' => 'Batal']);

            throw new RuntimeException('rollback');
        });
    } catch (RuntimeException) {
    }

    expect(Cache::has('settings'))->toBeTrue()->and(Settings::get('site_name'))->toBe('BlueCore Course');
});
