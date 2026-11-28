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
        ->assertSeeText('Slogan (di bawah nama situs pada logo dan footer)')
        ->assertSeeText('Deskripsi singkat (footer dan meta)')
        ->assertSeeText('Nama perusahaan untuk hak cipta')
        ->assertSeeText('Kosongkan untuk memakai nama situs.')
        ->assertSeeText('Disclaimer di footer')
        ->assertDontSee('name="checkout_guarantee"', false)
        ->assertSeeInOrder(['Teks kecil di atas judul hero', 'Judul hero', 'Subjudul hero', 'Teks di bawah daftar kursus', 'Judul bagian testimoni', 'Judul ajakan penutup', 'Teks ajakan penutup'])
        ->assertDontSeeText('Format internasional tanpa tanda plus, contoh 628123456789.')
        ->assertSeeText('Tautan toko untuk ikon Marketplace di footer. Kosongkan jika belum punya toko di marketplace tersebut.')
        ->assertSeeText('Biaya admin per pesanan (rupiah)')
        ->assertSeeText('Ditambahkan ke setiap pesanan kursus berbayar.')
        ->assertSeeText('Kursus ini tampil di hero beranda dan menjadi tujuan tombol BELI KURSUS SEKARANG.')
        ->assertSeeText('Otomatis: kursus terbit terbaru')
        ->assertSeeText('Landing untuk tombol Tonton video gratis')
        ->assertSeeText('Tidak ada, sembunyikan tombol')
        ->assertDontSee('name="lead_magnet_youtube_url"', false)
        ->assertSee('placeholder="G-XXXXXXXXXX"', false)
        ->assertSee('value="Bluecore Course"', false)
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
    'long hero title' => [['hero_title' => str_repeat('a', 121)], 'hero_title', 'Judul hero maksimal 120 karakter.'],
    'long hero eyebrow' => [['hero_eyebrow' => str_repeat('a', 61)], 'hero_eyebrow', 'Teks kecil hero maksimal 60 karakter.'],
    'long hero subtitle' => [['hero_subtitle' => str_repeat('a', 201)], 'hero_subtitle', 'Subjudul hero maksimal 200 karakter.'],
    'long testimonials title' => [['testimonials_title' => str_repeat('a', 121)], 'testimonials_title', 'Judul testimoni maksimal 120 karakter.'],
    'long closing title' => [['cta_title' => str_repeat('a', 121)], 'cta_title', 'Judul ajakan penutup maksimal 120 karakter.'],
    'long closing text' => [['cta_text' => str_repeat('a', 301)], 'cta_text', 'Teks ajakan penutup maksimal 300 karakter.'],
    'long course list text' => [['home_courses_text' => str_repeat('a', 2001)], 'home_courses_text', 'Teks di bawah daftar kursus maksimal 2.000 karakter.'],
    'long company name' => [['company_name' => str_repeat('a', 121)], 'company_name', 'Nama perusahaan maksimal 120 karakter.'],
    'long disclaimer' => [['footer_disclaimer' => str_repeat('a', 601)], 'footer_disclaimer', 'Disclaimer maksimal 600 karakter.'],
    'instagram over http' => [['instagram_url' => 'http://instagram.com/x'], 'instagram_url', 'Tautan Instagram harus diawali https://.'],
    'tiktok shop over http' => [['tiktokshop_url' => 'http://shop.tiktok.com/x'], 'tiktokshop_url', 'Tautan TikTok Shop harus diawali https://.'],
    'negative fee' => [['admin_fee' => '-1'], 'admin_fee', 'Biaya admin harus angka bulat 0 sampai 1.000.000.'],
    'fee too high' => [['admin_fee' => '1000001'], 'admin_fee', 'Biaya admin harus angka bulat 0 sampai 1.000.000.'],
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

    expect(Cache::has('settings'))->toBeTrue()->and(Settings::get('site_name'))->toBe('Bluecore Course');
});

it('lets the admin pick a landing page for the Nonton gratis button', function () {
    $landing = App\Models\Page::factory()->create(['title' => 'Lele untuk Pemula', 'slug' => 'lele-pemula', 'youtube_url' => 'https://youtu.be/M7lc1UVf-VE']);
    App\Models\Page::factory()->create(['title' => 'Tentang Kami', 'slug' => 'tentang-kami']);

    $this->actingAs($this->admin)->get('/admin/settings')
        ->assertSeeText('Lele untuk Pemula (/lele-pemula)')
        ->assertDontSeeText('Tentang Kami (/tentang-kami)');

    saveSettings(['free_video_page_id' => $landing->id])->assertSessionHasNoErrors();

    expect(Settings::get('free_video_page_id'))->toBe($landing->id);
});

it('refuses a page without a video for the Nonton gratis button', function () {
    $page = App\Models\Page::factory()->create(['slug' => 'tentang-kami']);

    saveSettings(['free_video_page_id' => $page->id])->assertSessionHasErrors(['free_video_page_id' => 'Pilih halaman landing yang punya video.']);
});

it('marks draft landing pages and leaves out pages whose link is not a video', function () {
    App\Models\Page::factory()->create(['title' => 'Lele Draf', 'slug' => 'lele-draf', 'youtube_url' => 'https://youtu.be/M7lc1UVf-VE', 'is_published' => false]);
    App\Models\Page::factory()->create(['title' => 'Tautan Lama', 'slug' => 'tautan-lama', 'youtube_url' => 'https://contoh.id/video']);

    $this->actingAs($this->admin)->get('/admin/settings')
        ->assertSeeText('Lele Draf (/lele-draf, draf)')
        ->assertDontSeeText('Tautan Lama (/tautan-lama)');
});
