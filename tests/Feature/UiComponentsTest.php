<?php

use App\Enums\OrderStatus;

it('renders a labelled field with helper text', function () {
    $html = (string) $this->blade('<x-field name="phone" label="Nomor telepon" type="tel" help="Wajib diisi. Format 08xx atau 628xx." />');

    expect($html)->toContain('<label for="phone">Nomor telepon</label>')
        ->toContain('id="phone-help"')
        ->toContain('Wajib diisi. Format 08xx atau 628xx.')
        ->toContain('aria-describedby="phone-help"')
        ->not->toContain('aria-invalid');
});

it('renders inline field errors', function () {
    $html = (string) $this->withViewErrors(['email' => 'Format email belum benar, contoh nama@domain.com.'])
        ->blade('<x-field name="email" label="Email" type="email" />');

    expect($html)->toContain('aria-invalid="true"')
        ->toContain('id="email-error"')
        ->toContain('class="field__error"')
        ->toContain('Format email belum benar, contoh nama@domain.com.');
});

it('keeps old input in text fields', function () {
    // old() membaca session milik request, jadi request diberi session dulu.
    request()->setLaravelSession($session = app('session.store'));
    $session->flashInput(['name' => 'Budi Santoso']);

    expect((string) $this->blade('<x-field name="name" label="Nama" />'))->toContain('value="Budi Santoso"');
});

it('renders textarea and select fields', function () {
    expect((string) $this->blade('<x-field name="note" label="Catatan" type="textarea" value="Isi" />'))->toContain('<textarea')->toContain('Isi</textarea>')
        ->and((string) $this->blade('<x-field name="status" label="Status" type="select"><option value="1">Aktif</option></x-field>'))->toContain('<select')->toContain('<option value="1">Aktif</option>');
});

it('never refills password fields and offers a visibility toggle', function () {
    request()->setLaravelSession($session = app('session.store'));
    $session->flashInput(['password' => 'rahasia123']);

    $html = (string) $this->blade('<x-password-input name="password" label="Kata sandi" help="Minimal 8 karakter." />');

    expect($html)->not->toContain('rahasia123')
        ->toContain('type="password"')
        ->toContain('Tampilkan kata sandi')
        ->toContain('aria-pressed="false"')
        ->toContain('Minimal 8 karakter.');
});

it('shows the slug helper text', function () {
    expect((string) $this->blade('<x-slug-field source="title" :auto="true" />'))
        ->toContain('Terisi otomatis dari judul, bisa diubah.')
        ->toContain('name="slug"');
});

it('labels the date range', function () {
    expect((string) $this->blade('<x-date-range from="2026-10-01" />'))
        ->toContain('Dari tanggal')
        ->toContain('Sampai tanggal')
        ->toContain('value="2026-10-01"')
        ->toContain('type="date"');
});

it('lays out two buttons as an equal pair', function () {
    expect((string) $this->blade('<x-button-pair><button>Batal</button><button>Simpan</button></x-button-pair>'))
        ->toContain('class="btn-pair"');
});

it('renders an accessible modal', function () {
    $html = (string) $this->blade('<x-modal name="hapus" title="Hapus halaman?"><p>Isi</p></x-modal>');

    expect($html)->toContain('role="dialog"')
        ->toContain('aria-modal="true"')
        ->toContain('Hapus halaman?')
        ->toContain('aria-label="Tutup"');
});

it('renders an empty state with its action', function () {
    expect((string) $this->blade('<x-empty-state message="Tidak ada data."><a href="/courses">Lihat kursus</a></x-empty-state>'))
        ->toContain('class="empty"')
        ->toContain('Tidak ada data.')
        ->toContain('Lihat kursus');
});

it('labels order statuses with a tone', function (OrderStatus $status, string $label, string $tone) {
    expect((string) $this->blade('<x-status-badge :status="$status" />', ['status' => $status]))
        ->toContain($label)
        ->toContain($tone);
})->with([
    [OrderStatus::Pending, 'Menunggu pembayaran', 'badge--warn'],
    [OrderStatus::Paid, 'Lunas', 'badge--ok'],
    [OrderStatus::Failed, 'Gagal', 'badge--bad'],
    [OrderStatus::Refunded, 'Dikembalikan', 'badge'],
]);

it('draws a thumbnail placeholder with the title initials', function () {
    expect((string) $this->blade('<x-thumbnail-placeholder title="Budidaya Lele Galon" />'))
        ->toContain('aria-label="Thumbnail Budidaya Lele Galon"')
        ->toContain('>BL</text>');
});

it('can start a modal open, for example after a failed submit', function () {
    expect((string) $this->blade('<x-modal name="kategori" title="Kategori baru" :show="true"><p>Isi</p></x-modal>'))
        ->toContain('x-data="{ open: true }"')
        ->and((string) $this->blade('<x-modal name="kategori" title="Kategori baru"><p>Isi</p></x-modal>'))
        ->toContain('x-data="{ open: false }"');
});

it('keeps helper text, errors and buttons out of the accessible label', function () {
    $field = (string) $this->withViewErrors(['phone' => 'Nomor telepon wajib diisi.'])
        ->blade('<x-field name="phone" label="Nomor telepon" help="Format 08xx atau 628xx." />');
    $password = (string) $this->blade('<x-password-input name="password" label="Kata sandi" help="Minimal 8 karakter." />');

    expect($field)->toContain('<div class="field">')
        ->toContain('<label for="phone">Nomor telepon</label>')
        ->and($password)->toContain('<label for="password">Kata sandi</label>')
        ->and(preg_match('/<label[^>]*>[^<]*<\/label>/', $field))->toBe(1);
});
