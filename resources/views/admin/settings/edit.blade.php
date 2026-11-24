<x-admin.layout title="Pengaturan">
    <x-form method="PUT" :action="route('admin.settings.update')" class="settings-form" enctype="multipart/form-data">
        <section class="panel">
            <h2 class="panel-title">Identitas</h2>
            <div class="form-grid form-grid--2">
                <x-field name="site_name" label="Nama situs" :value="$settings['site_name']" required maxlength="60" />
                <x-field name="site_slogan" label="Slogan (di bawah nama situs pada logo dan judul beranda)" :value="$settings['site_slogan']" maxlength="120" />
                <x-field name="site_description" label="Deskripsi singkat (beranda, footer, meta)" type="textarea" :value="$settings['site_description']" :full="true" rows="3" maxlength="300" />
                <div class="logo-field field--full">
                    <img class="logo-field__preview" src="{{ \App\Support\SiteLogo::url() }}" alt="Logo saat ini" height="56">
                    <div class="form-grid">
                        <x-field name="logo_upload" label="Logo" type="file" accept="image/jpeg,image/png,image/webp" />
                        @if ($settings['logo'])
                            <x-checkbox name="remove_logo" label="Hapus logo" />
                        @endif
                    </div>
                </div>
            </div>
        </section>

        <section class="panel">
            <h2 class="panel-title">Kontak</h2>
            <div class="form-grid form-grid--2">
                <x-field name="address" label="Alamat" :value="$settings['address']" maxlength="200" />
                <x-field name="phone" label="Telepon" type="tel" :value="$settings['phone']" maxlength="30" inputmode="tel" />
                <x-field name="whatsapp" label="WhatsApp" :value="$settings['whatsapp']" help="Format internasional tanpa tanda plus, contoh 628123456789." inputmode="numeric" />
                <x-field name="email" label="Email" type="email" :value="$settings['email']" maxlength="150" />
            </div>
        </section>

        <section class="panel">
            <h2 class="panel-title">Sosial</h2>
            <div class="form-grid form-grid--2">
                <x-field name="instagram_url" label="Tautan Instagram" type="url" :value="$settings['instagram_url']" placeholder="https://" inputmode="url" />
                <x-field name="youtube_url" label="Tautan YouTube" type="url" :value="$settings['youtube_url']" placeholder="https://" inputmode="url" />
                <x-field name="tiktok_url" label="Tautan TikTok" type="url" :value="$settings['tiktok_url']" placeholder="https://" inputmode="url" />
            </div>
        </section>

        <section class="panel">
            <h2 class="panel-title">Marketplace</h2>
            <p class="field__help">Tautan toko untuk ikon Marketplace di footer. Kosongkan jika belum punya toko di marketplace tersebut.</p>
            <div class="form-grid form-grid--2">
                <x-field name="shopee_url" label="Tautan Shopee" type="url" :value="$settings['shopee_url']" placeholder="https://" inputmode="url" />
                <x-field name="tokopedia_url" label="Tautan Tokopedia" type="url" :value="$settings['tokopedia_url']" placeholder="https://" inputmode="url" />
                <x-field name="tiktokshop_url" label="Tautan TikTok Shop" type="url" :value="$settings['tiktokshop_url']" placeholder="https://" inputmode="url" />
            </div>
        </section>

        <section class="panel">
            <h2 class="panel-title">Pembayaran</h2>
            <div class="form-grid form-grid--2">
                <x-field name="admin_fee" label="Biaya admin per pesanan (rupiah)" type="number" :value="$settings['admin_fee']" help="Ditambahkan ke setiap pesanan kursus berbayar." required min="0" max="1000000" step="1" inputmode="numeric" />
            </div>
        </section>

        <section class="panel">
            <h2 class="panel-title">Beranda</h2>
            <div class="form-grid form-grid--2">
                <x-field name="featured_course_id" label="Kursus unggulan di hero" type="select" :options="['' => 'Otomatis: kursus terbit terbaru'] + $publishedCourses" :value="$settings['featured_course_id']" help="Kursus ini tampil di hero beranda dan menjadi tujuan tombol Lihat kursus." />
            </div>
        </section>

        <section class="panel">
            <h2 class="panel-title">Video gratis</h2>
            <p class="field__help">Tampil di halaman Nonton gratis untuk pengguna yang sudah daftar. Kosongkan tautan untuk menyembunyikan tombol Nonton gratis di beranda.</p>
            <div class="form-grid form-grid--2">
                <x-field name="lead_magnet_title" label="Judul video" :value="$settings['lead_magnet_title']" maxlength="150" />
                <x-field name="lead_magnet_youtube_url" label="Tautan YouTube" type="url" :value="$settings['lead_magnet_youtube_url']" placeholder="https://youtu.be/..." inputmode="url" />
                <x-field name="lead_magnet_description" label="Deskripsi" type="textarea" :value="$settings['lead_magnet_description']" help="Tampil di bawah judul video. Pisahkan paragraf dengan satu baris kosong." :full="true" rows="6" maxlength="5000" />
            </div>
        </section>

        <section class="panel">
            <h2 class="panel-title">Pelacakan</h2>
            <div class="form-grid form-grid--2">
                <x-field name="ga4_measurement_id" label="ID pengukuran Google Analytics 4" :value="$settings['ga4_measurement_id']" placeholder="G-XXXXXXXXXX" autocapitalize="characters" spellcheck="false" />
                <x-field name="meta_pixel_id" label="ID Meta Pixel" :value="$settings['meta_pixel_id']" inputmode="numeric" />
            </div>
        </section>

        <div class="btn-row">
            <button type="submit" class="btn btn--brand">Simpan pengaturan</button>
            <span class="muted small">Perubahan langsung berlaku di seluruh halaman tanpa deploy ulang.</span>
        </div>
    </x-form>
</x-admin.layout>
