<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentCallbackStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\PaymentCallback;
use App\Models\User;
use App\Notifications\PaymentSucceededNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    config(['duitku.merchant_code' => 'DS12345', 'duitku.api_key' => 'kunci', 'duitku.retry_sleep_ms' => 0]);
    $this->admin = User::factory()->admin()->create();
    $this->buyer = User::factory()->create(['name' => 'Sari Lestari', 'email' => 'sari@contoh.id']);
    $this->course = Course::factory()->published()->create(['title' => 'Lele Galon']);
});

function adminOrder(array $attributes = []): Order
{
    return Order::factory()->for(test()->buyer)->for(test()->course)->create(['amount' => 150000, 'duitku_reference' => 'DSREF', ...$attributes]);
}

it('shows the orders tab and lists orders with filters', function () {
    $order = adminOrder();
    Order::factory()->create(['status' => OrderStatus::Paid, 'paid_at' => now()]);

    $this->actingAs($this->admin)->get('/admin/orders')
        ->assertOk()
        ->assertSee('<span>Pesanan</span>', false)
        ->assertSeeText('Cari nomor atau pembeli')
        ->assertSeeInOrder(['Nomor', 'Pembeli', 'Kursus', 'Total', 'Status', 'Dibuat', 'Aksi'])
        ->assertSeeText($order->order_number)
        ->assertSeeText('Sari Lestari')
        ->assertSee('aria-label="Tandai lunas"', false)
        ->assertSee('aria-label="Batalkan pesanan"', false)
        ->assertSee('aria-label="Cek status ke Duitku"', false);
});

it('combines search, status and date filters', function () {
    $match = adminOrder(['created_at' => Carbon::parse('2026-11-10 10:00', 'Asia/Jakarta')]);
    $otherStatus = adminOrder(['status' => OrderStatus::Paid, 'paid_at' => now(), 'created_at' => Carbon::parse('2026-11-10 11:00', 'Asia/Jakarta')]);
    $otherDay = adminOrder(['created_at' => Carbon::parse('2026-11-20 10:00', 'Asia/Jakarta')]);
    $otherBuyer = Order::factory()->create(['created_at' => Carbon::parse('2026-11-10 10:00', 'Asia/Jakarta')]);

    $this->actingAs($this->admin)->get('/admin/orders?q=sari%40contoh&status=pending&from=2026-11-10&to=2026-11-10')
        ->assertSeeText($match->order_number)
        ->assertDontSeeText($otherStatus->order_number)
        ->assertDontSeeText($otherDay->order_number)
        ->assertDontSeeText($otherBuyer->order_number)
        ->assertSee('>Hapus filter</a>', false);

    $this->get('/admin/orders?q='.$otherBuyer->order_number)->assertSeeText($otherBuyer->order_number)->assertDontSeeText($match->order_number);
    $this->get('/admin/orders?q=tidak-ada')->assertSeeText('Tidak ada pesanan yang cocok dengan filter ini.');
});

it('refuses non-admins', function () {
    $order = adminOrder();

    $this->actingAs($this->buyer)->get('/admin/orders')->assertForbidden();
    $this->actingAs($this->buyer)->post('/admin/orders/'.$order->uuid.'/mark-paid', ['paid_note' => 'x'])->assertForbidden();
});

it('marks an order paid with a required note, once', function () {
    $order = adminOrder(['status' => OrderStatus::Expired]);

    $html = $this->actingAs($this->admin)->from('/admin/orders')->followingRedirects()
        ->post('/admin/orders/'.$order->uuid.'/mark-paid', ['_modal' => 'mark-paid-'.$order->id, 'paid_note' => ''])->getContent();
    expect($html)->toContain('id="mark-paid-'.$order->id.'-paid_note-error" role="alert">Catatan wajib diisi.')
        ->and($order->fresh()->status)->toBe(OrderStatus::Expired);

    $this->from('/admin/orders')->post('/admin/orders/'.$order->uuid.'/mark-paid', ['_modal' => 'mark-paid-'.$order->id, 'paid_note' => 'Transfer BCA 25 Nov'])
        ->assertSessionHas('status', 'Pesanan '.$order->order_number.' ditandai lunas. Akses dibuka dan email konfirmasi terkirim ke pembeli.');
    $this->from('/admin/orders')->post('/admin/orders/'.$order->uuid.'/mark-paid', ['_modal' => 'mark-paid-'.$order->id, 'paid_note' => 'Lagi'])
        ->assertSessionHas('error');

    expect($order->fresh())->status->toBe(OrderStatus::Paid)->confirmed_by_user_id->toBe($this->admin->id)->paid_note->toBe('Transfer BCA 25 Nov')
        ->and(Enrollment::query()->where('order_id', $order->id)->count())->toBe(1);
    Notification::assertSentToTimes($this->buyer, PaymentSucceededNotification::class, 1);
});

it('refuses to mark refunded orders paid', function () {
    $order = adminOrder(['status' => OrderStatus::Refunded]);

    $this->actingAs($this->admin)->from('/admin/orders')->post('/admin/orders/'.$order->uuid.'/mark-paid', ['_modal' => 'mark-paid-'.$order->id, 'paid_note' => 'x'])
        ->assertSessionHas('error');

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);
});

it('cancels only pending orders', function () {
    $pending = adminOrder();
    $paid = adminOrder(['status' => OrderStatus::Paid, 'paid_at' => now()]);

    $this->actingAs($this->admin)->from('/admin/orders')->post('/admin/orders/'.$pending->uuid.'/cancel')
        ->assertSessionHas('status', 'Pesanan '.$pending->order_number.' dibatalkan.');
    $this->from('/admin/orders')->post('/admin/orders/'.$paid->uuid.'/cancel')->assertSessionHas('error');

    expect($pending->fresh()->status)->toBe(OrderStatus::Cancelled)->and($paid->fresh()->status)->toBe(OrderStatus::Paid);
});

it('checks the status at Duitku and reports the result', function (string $code, string $toastKey, OrderStatus $expected) {
    Http::fake(['*/api/merchant/transactionStatus' => Http::response(['statusCode' => $code, 'amount' => '150000', 'reference' => 'DSREF'])]);
    $order = adminOrder();

    $this->actingAs($this->admin)->from('/admin/orders')->post('/admin/orders/'.$order->uuid.'/check-status')->assertSessionHas($toastKey);

    expect($order->fresh()->status)->toBe($expected);
})->with([
    'paid' => ['00', 'status', OrderStatus::Paid],
    'pending' => ['01', 'status', OrderStatus::Pending],
    'failed' => ['02', 'status', OrderStatus::Pending],
]);

it('says when Duitku cannot be reached', function () {
    Http::fake(['*' => Http::failedConnection()]);
    $order = adminOrder();

    $this->actingAs($this->admin)->from('/admin/orders')->post('/admin/orders/'.$order->uuid.'/check-status')
        ->assertSessionHas('error', 'Gagal menghubungi Duitku. Coba lagi.');
});

it('records a refund with a note and closes the access from this order only', function () {
    $order = adminOrder(['status' => OrderStatus::Paid, 'paid_at' => now()]);
    Enrollment::factory()->for($this->buyer)->for($this->course)->create(['order_id' => $order->id]);

    $this->actingAs($this->admin)->get('/admin/orders')->assertSeeText('Akses Sari Lestari ke kursus ini akan ditutup.');

    $html = $this->from('/admin/orders')->followingRedirects()
        ->post('/admin/orders/'.$order->uuid.'/refund', ['_modal' => 'refund-'.$order->id, 'refund_note' => ''])->getContent();
    expect($html)->toContain('id="refund-'.$order->id.'-refund_note-error" role="alert">Catatan wajib diisi.');

    $this->from('/admin/orders')->post('/admin/orders/'.$order->uuid.'/refund', ['_modal' => 'refund-'.$order->id, 'refund_note' => 'Transfer balik BCA 26 Nov'])
        ->assertSessionHas('status', 'Pengembalian dana pesanan '.$order->order_number.' dicatat. Akses kursus ditutup.');

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded)
        ->and(Enrollment::query()->count())->toBe(0);
    $this->get('/admin')->assertSeeText('Dari 0 pesanan lunas');
});

it('refuses a refund for an order that is not paid', function () {
    $order = adminOrder();

    $this->actingAs($this->admin)->from('/admin/orders')->post('/admin/orders/'.$order->uuid.'/refund', ['_modal' => 'refund-'.$order->id, 'refund_note' => 'x'])
        ->assertSessionHas('error');
});

it('shows the admin tools and callback history on the order page without signatures', function () {
    Http::fake(['*/api/merchant/transactionStatus' => Http::response(['statusCode' => '01', 'amount' => '150000'])]);
    $order = adminOrder();
    PaymentCallback::factory()->create(['order_id' => $order->id, 'merchant_order_id' => $order->order_number, 'result_code' => '01', 'status' => PaymentCallbackStatus::Rejected, 'payload' => ['resultCode' => '01']]);

    $this->actingAs($this->admin)->get('/order/'.$order->uuid)
        ->assertSeeText('Tindakan admin')
        ->assertSee('aria-label="Tandai lunas"', false)
        ->assertSeeText('Riwayat callback')
        ->assertSeeInOrder(['Waktu', 'Kode hasil', 'Status proses', '01', 'Ditolak'])
        ->assertDontSee('signature', false);
});

it('links the dashboard and the user list to orders', function () {
    $order = adminOrder();

    $this->actingAs($this->admin)->get('/admin')->assertSee('href="'.route('order.show', $order).'"', false);
    $this->get('/admin/users')->assertSee('href="'.route('admin.orders.index', ['q' => 'sari@contoh.id']).'"', false);
});
