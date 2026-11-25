<?php

use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create(['name' => 'Admin Utama', 'email' => 'admin@contoh.id']);
});

function exportRows(string $query = ''): array
{
    $response = test()->actingAs(test()->admin)->get('/admin/users/export'.$query)->assertOk();
    $content = $response->streamedContent();

    expect($content)->toStartWith("\xEF\xBB\xBF");

    return array_map(fn ($line) => str_getcsv($line, ';', '"', ''), array_filter(explode("\n", substr($content, 3))));
}

it('exports the PRD columns with semicolons, a BOM and a dated file name', function () {
    $this->travelTo(now()->setDate(2026, 11, 24)->setTime(16, 22));
    $buyer = User::factory()->create(['name' => 'Sari Lestari', 'email' => 'sari@contoh.id', 'phone' => '08123456789', 'signup_source' => 'free_video']);
    Enrollment::factory()->count(2)->for($buyer)->create();
    Order::factory()->paid()->for($buyer)->create(['amount' => 150000]);

    $response = $this->actingAs($this->admin)->get('/admin/users/export');
    expect($response->headers->get('content-disposition'))->toContain('pengguna-20261124-1622.csv');

    $rows = exportRows();
    expect($rows[0])->toBe(['Nama', 'Email', 'Telepon', 'Peran', 'Terverifikasi', 'Sumber daftar', 'Tanggal daftar', 'Jumlah kursus', 'Total belanja lunas'])
        ->and($rows[2])->toBe(['Sari Lestari', 'sari@contoh.id', '08123456789', 'Pengguna', 'Ya', 'Video gratis', '2026-11-24 16:22', '2', '150000']);
});

it('follows the active filters', function () {
    User::factory()->create(['name' => 'Sari Lestari', 'email' => 'sari@contoh.id']);
    User::factory()->create(['name' => 'Budi Santoso', 'email' => 'budi@contoh.id']);

    $names = array_column(array_slice(exportRows('?q=lestari'), 1), 0);
    expect($names)->toBe(['Sari Lestari']);

    $names = array_column(array_slice(exportRows('?role=admin'), 1), 0);
    expect($names)->toBe(['Admin Utama']);
});

it('neutralizes cells that a spreadsheet would run as formulas', function () {
    User::factory()->unverified()->create(['name' => '=HYPERLINK("http://jahat.test")', 'phone' => '+628123456789', 'signup_source' => 'course']);

    $row = exportRows()[2];
    expect($row[0])->toBe('\'=HYPERLINK("http://jahat.test")')
        ->and($row[2])->toBe("'+628123456789")
        ->and($row[4])->toBe('Tidak')
        ->and($row[5])->toBe('Halaman kursus');
});

it('logs who exported and how many rows', function () {
    Log::spy();
    User::factory()->count(2)->create();

    exportRows();

    Log::shouldHaveReceived('info')->withArgs(fn ($message, $context) => $message === 'Admin exported users' && $context['admin_id'] === $this->admin->id && $context['rows'] === 3);
});

it('allows five exports a minute', function () {
    foreach (range(1, 5) as $attempt) {
        $this->actingAs($this->admin)->get('/admin/users/export')->assertOk();
    }

    $this->get('/admin/users/export')->assertStatus(429);
});

it('neutralizes every cell start a spreadsheet treats as a formula', function (string $name) {
    User::factory()->create(['name' => $name, 'email' => 'satu@contoh.id']);

    $row = collect(array_slice(exportRows(), 1))->firstWhere(1, 'satu@contoh.id');

    expect($row[0])->toBe("'".$name);
})->with(['=1+1', '+1', '-2+3', '@SUM(1)', "\t=1", "\r=1"]);
