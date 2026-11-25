<?php

use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    config(['duitku.merchant_code' => 'DS12345', 'duitku.api_key' => 'kunci-rahasia', 'duitku.retry_sleep_ms' => 0, 'duitku.js_url' => 'https://app-sandbox.duitku.com/lib/js/duitku.js']);
    $this->owner = User::factory()->create(['name' => 'Sari Lestari']);
    $this->course = Course::factory()->published()->create(['title' => 'Lele Galon', 'slug' => 'lele-galon']);
});

function statusReply(string ...$codes): void
{
    $sequence = Http::sequence();
    foreach ($codes as $code) {
        $sequence->push(['statusCode' => $code, 'amount' => '150000', 'reference' => 'DSREF']);
    }

    Http::fake(['*/api/merchant/transactionStatus' => $sequence]);
}

function ownerOrder(array $attributes = []): Order
{
    return Order::factory()->for(test()->owner)->for(test()->course)->create([
        'subtotal' => 150000, 'admin_fee' => 0, 'amount' => 150000, 'duitku_reference' => 'DSREF',
        'payment_url' => 'https://app-sandbox.duitku.com/pay/DSREF', 'expires_at' => now()->addHours(5), ...$attributes,
    ]);
}

it('shows a pending order with the invoice details and payment buttons', function () {
    statusReply('01');
    Settings::set(['admin_fee' => 0]);
    $order = ownerOrder(['subtotal' => 147500, 'admin_fee' => 2500]);

    $html = $this->actingAs($this->owner)->get('/order/'.$order->uuid)
        ->assertOk()
        ->assertSeeText('Nomor pesanan')
        ->assertSee('<h1 class="num">'.$order->order_number.'</h1>', false)
        ->assertSee('aria-label="Salin nomor pesanan"', false)
        ->assertSeeText('Total tagihan')
        ->assertSeeText('Menunggu pembayaran')
        ->assertSeeText('Akses kursus terbuka otomatis setelah pembayaran diterima.')
        ->assertSeeInOrder(['Harga kursus', 'Rp 147.500', 'Biaya admin', 'Rp 2.500', 'Total', 'Rp 150.000'])
        ->assertSeeInOrder(['Pembeli', 'Sari Lestari', 'Bayar sebelum'])
        ->assertSeeText('Bayar sekarang')
        ->assertSeeText('Perbarui status')
        ->assertSeeText('Batalkan pesanan')
        ->assertSeeText('Pesanan saya')
        ->assertSee('src="https://app-sandbox.duitku.com/lib/js/duitku.js"', false)
        ->getContent();

    expect($html)->toContain('DSREF')
        ->toContain('data-reference="DSREF"')
        ->toContain('data-payment-url="https://app-sandbox.duitku.com/pay/DSREF"')
        ->toContain('href="https://app-sandbox.duitku.com/pay/DSREF"')
        ->not->toContain('kunci-rahasia');
});

it('opens the popup only when asked with pay=1', function () {
    statusReply('01');
    $order = ownerOrder();

    expect($this->actingAs($this->owner)->get('/order/'.$order->uuid.'?pay=1')->getContent())->toContain('data-auto-open="true"')
        ->and($this->get('/order/'.$order->uuid)->getContent())->toContain('data-auto-open="false"');
});

it('shows the right note and buttons for every status', function (array $attributes, array $texts, array $missing) {
    statusReply('01');
    $order = ownerOrder($attributes);

    $response = $this->actingAs($this->owner)->get('/order/'.$order->uuid)->assertOk();
    foreach ($texts as $text) {
        $response->assertSeeText($text);
    }
    foreach ($missing as $text) {
        $response->assertDontSeeText($text);
    }
})->with([
    'pending past due' => [['expires_at' => now()->subHour()], ['Batas waktu bayar sudah lewat', 'pembayaran tetap diproses dan akses kursus dibuka'], []],
    'paid' => [['status' => OrderStatus::Paid, 'paid_at' => now(), 'duitku_payment_code' => 'SP'], ['Pembayaran diterima', 'Akses kursus sudah terbuka.', 'Total dibayar', 'Mulai belajar', 'Cetak invoice', 'Metode bayar', 'ShopeePay', 'Dibayar'], ['Bayar sekarang', 'Bayar sebelum']],
    'expired' => [['status' => OrderStatus::Expired], ['Pesanan kedaluwarsa', 'Buat pesanan baru untuk membeli kursus ini.', 'Beli lagi'], ['Bayar sekarang']],
    'cancelled' => [['status' => OrderStatus::Cancelled], ['Pesanan dibatalkan', 'Buat pesanan baru jika ingin membeli kursus ini.', 'Beli lagi'], ['Bayar sekarang']],
    'failed' => [['status' => OrderStatus::Failed], ['Pembayaran gagal', 'Buat pesanan baru untuk mencoba lagi dengan metode lain.', 'Beli lagi'], ['Bayar sekarang']],
    'refunded' => [['status' => OrderStatus::Refunded, 'refunded_at' => now()], ['Dana dikembalikan', 'Akses kursus ditutup.', 'Beli lagi'], ['Mulai belajar']],
]);

it('names the admin who marked the order paid', function () {
    $admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
    $order = ownerOrder(['status' => OrderStatus::Paid, 'paid_at' => now(), 'confirmed_by_user_id' => $admin->id]);

    $this->actingAs($this->owner)->get('/order/'.$order->uuid)->assertSeeInOrder(['Ditandai lunas oleh', 'Admin Utama']);
});

it('refuses other users and lets admins in with a link to all orders', function () {
    statusReply('01');
    $order = ownerOrder();

    $this->actingAs(User::factory()->create())->get('/order/'.$order->uuid)->assertForbidden()->assertSeeText('Pesanan ini milik akun lain.');
    $this->actingAs(User::factory()->admin()->create())->get('/order/'.$order->uuid)
        ->assertOk()
        ->assertSeeText('Semua pesanan')
        ->assertDontSeeText('Bayar sekarang');
});

it('checks Duitku at most once every 30 seconds while pending and pays a confirmed order', function () {
    statusReply('01', '00');
    $order = ownerOrder();

    $this->actingAs($this->owner)->get('/order/'.$order->uuid);
    $this->get('/order/'.$order->uuid);
    Http::assertSentCount(1);

    $this->travel(31)->seconds();
    $this->get('/order/'.$order->uuid)->assertSeeText('Pembayaran diterima');

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('ignores a failed or mismatched status and survives Duitku being down', function (string $code, int $amount) {
    Http::fake(['*/api/merchant/transactionStatus' => Http::response(['statusCode' => $code, 'amount' => (string) $amount, 'reference' => 'DSREF'])]);
    $order = ownerOrder();

    $this->actingAs($this->owner)->get('/order/'.$order->uuid)->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
})->with([
    'failed status' => ['02', 150000],
    'amount mismatch' => ['00', 1000],
]);

it('keeps the page up when Duitku cannot be reached', function () {
    Http::fake(['*/api/merchant/transactionStatus' => Http::failedConnection()]);
    $order = ownerOrder();

    $this->actingAs($this->owner)->get('/order/'.$order->uuid)->assertOk()->assertSeeText('Menunggu pembayaran');
});

it('answers status from the database only', function () {
    Http::fake();
    $order = ownerOrder();

    $this->actingAs($this->owner)->getJson('/order/'.$order->uuid.'/status')->assertOk()->assertJson(['status' => 'pending']);
    Http::assertNothingSent();
    $this->actingAs(User::factory()->create())->getJson('/order/'.$order->uuid.'/status')->assertForbidden();
});

it('refreshes with its own short lock', function () {
    statusReply('01', '00');
    $order = ownerOrder();
    $this->actingAs($this->owner)->get('/order/'.$order->uuid);

    $this->postJson('/order/'.$order->uuid.'/refresh')->assertOk()->assertJson(['status' => 'paid']);
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);

    $this->actingAs(User::factory()->create())->postJson('/order/'.$order->uuid.'/refresh')->assertForbidden();
});

it('redirects a form refresh back with a toast', function () {
    statusReply('01');
    $order = ownerOrder();

    $this->actingAs($this->owner)->from('/order/'.$order->uuid)->post('/order/'.$order->uuid.'/refresh')
        ->assertRedirect('/order/'.$order->uuid)
        ->assertSessionHas('status', 'Pembayaran belum diterima. Coba lagi beberapa saat lagi.');
});

it('sends the purchase event once when the owner first sees the paid order', function () {
    Settings::set(['ga4_measurement_id' => 'G-ABC123', 'meta_pixel_id' => '1234567890']);
    $order = ownerOrder(['status' => OrderStatus::Paid, 'paid_at' => now()]);

    $this->actingAs(User::factory()->admin()->create())->get('/order/'.$order->uuid);
    expect($order->fresh()->purchase_tracked_at)->toBeNull();

    $first = $this->actingAs($this->owner)->get('/order/'.$order->uuid)->getContent();
    $second = $this->get('/order/'.$order->uuid)->getContent();

    expect($first)->toContain("gtag('event', 'purchase'")
        ->toContain('"transaction_id":"'.$order->order_number.'"')
        ->toContain('"value":150000')
        ->toContain("fbq('track', 'Purchase'")
        ->and($second)->not->toContain("'purchase'")
        ->and($order->fresh()->purchase_tracked_at)->not->toBeNull();
});

it('prints as an invoice with the site identity', function () {
    Settings::set(['site_name' => 'BlueCore Course', 'address' => 'Bogor', 'email' => 'support@bluecore.co.id']);
    $order = ownerOrder(['status' => OrderStatus::Paid, 'paid_at' => now()]);

    $this->actingAs($this->owner)->get('/order/'.$order->uuid)
        ->assertSee('<span class="print-only">Invoice</span>', false)
        ->assertSee('class="print-only print-head"', false)
        ->assertSeeInOrder(['print-head', 'BlueCore Course', 'Bogor', 'support@bluecore.co.id']);
});

it('lets the owner cancel a pending order, and explains why a paid one cannot be cancelled', function () {
    statusReply('01');
    $order = ownerOrder();

    $this->actingAs($this->owner)->from('/order/'.$order->uuid)->post('/order/'.$order->uuid.'/cancel')
        ->assertSessionHas('status', 'Pesanan '.$order->order_number.' dibatalkan.');
    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);

    $paid = ownerOrder(['status' => OrderStatus::Paid, 'paid_at' => now()]);
    $this->from('/order/'.$paid->uuid)->post('/order/'.$paid->uuid.'/cancel')
        ->assertRedirect('/order/'.$paid->uuid)
        ->assertSessionHas('error', 'Pesanan '.$paid->order_number.' sudah tidak menunggu pembayaran.')
        ->assertSessionMissing('status');
    expect($paid->fresh()->status)->toBe(OrderStatus::Paid);
    $this->actingAs(User::factory()->create())->post('/order/'.ownerOrder()->uuid.'/cancel')->assertForbidden();
});

it('shows the cancel confirmation text', function () {
    statusReply('01');
    $order = ownerOrder();

    $this->actingAs($this->owner)->get('/order/'.$order->uuid)
        ->assertSeeText('Pesanan '.$order->order_number.' akan dibatalkan. Jika Anda tetap membayar lewat tautan lama, pembayaran tetap diproses.');
});
