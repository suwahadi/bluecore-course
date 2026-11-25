<?php

use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use App\Notifications\OrderCreatedNotification;
use App\Support\Settings;
use App\Services\Orders\OrderService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Sleep;

beforeEach(function () {
    Notification::fake();
    config(['duitku.merchant_code' => 'DS12345', 'duitku.api_key' => 'kunci', 'duitku.retry_sleep_ms' => 0]);
    $this->user = User::factory()->create();
    $this->course = Course::factory()->published()->create(['title' => 'Lele Galon', 'slug' => 'lele-galon', 'price' => 150000, 'short_description' => 'Mulai dari satu galon.']);
});

function fakeInvoice(): void
{
    Http::fake(['*/api/merchant/createInvoice' => Http::response(['reference' => 'DS12345REF', 'paymentUrl' => 'https://app-sandbox.duitku.com/pay/DS12345REF', 'statusCode' => '00'])]);
}

it('asks guests to sign up and unverified users to verify', function () {
    $this->get('/courses/lele-galon/checkout')->assertStatus(401)->assertSee('next='.urlencode('/courses/lele-galon/checkout'), false);
    $this->actingAs(User::factory()->unverified()->create())->get('/courses/lele-galon/checkout')->assertRedirect(route('verification.notice'));
    $this->actingAs(User::factory()->unverified()->create())->post('/courses/lele-galon/checkout')->assertRedirect(route('verification.notice'));
});

it('shows the order summary with the admin fee and total', function () {
    Settings::set(['admin_fee' => 2500]);

    $this->actingAs($this->user)->get('/courses/lele-galon/checkout')
        ->assertOk()
        ->assertSee('<h1>Checkout</h1>', false)
        ->assertSeeText('Lele Galon')
        ->assertSeeText('Mulai dari satu galon.')
        ->assertSeeInOrder(['Harga kursus', 'Rp 150.000', 'Biaya admin', 'Rp 2.500', 'Total', 'Rp 152.500'])
        ->assertSeeText('Bayar sekarang')
        ->assertSeeText('Batas waktu bayar 24 jam.');
});

it('hides the admin fee line when there is none', function () {
    Settings::set(['admin_fee' => 0]);

    $this->actingAs($this->user)->get('/courses/lele-galon/checkout')->assertDontSeeText('Biaya admin');
});

it('sends owners to the player and buyers with an open order to that order', function () {
    Enrollment::factory()->for($this->user)->for($this->course)->create();
    $this->actingAs($this->user)->get('/courses/lele-galon/checkout')->assertRedirect(url('/learn/lele-galon'));

    $buyer = User::factory()->create();
    $open = Order::factory()->for($buyer)->for($this->course)->create(['expires_at' => now()->addHour()]);
    $this->actingAs($buyer)->get('/courses/lele-galon/checkout')->assertRedirect(url('/order/'.$open->uuid));
});

it('refuses checkout for drafts', function () {
    $this->course->update(['status' => 'draft']);

    $this->actingAs($this->user)->get('/courses/lele-galon/checkout')->assertNotFound();
    $this->actingAs($this->user)->post('/courses/lele-galon/checkout')->assertNotFound();
});

it('creates the order and its Duitku invoice, then opens the payment popup', function () {
    fakeInvoice();

    $response = $this->actingAs($this->user)->post('/courses/lele-galon/checkout');

    $order = Order::query()->sole();
    $response->assertRedirect(url('/order/'.$order->uuid.'?pay=1'));
    expect($order)->status->toBe(OrderStatus::Pending)->duitku_reference->toBe('DS12345REF')->payment_url->toBe('https://app-sandbox.duitku.com/pay/DS12345REF');
    Notification::assertSentTo($this->user, OrderCreatedNotification::class);
});

it('reuses the open order on a second post without a second invoice', function () {
    fakeInvoice();

    $this->actingAs($this->user)->post('/courses/lele-galon/checkout');
    $this->actingAs($this->user)->post('/courses/lele-galon/checkout');

    expect(Order::query()->count())->toBe(1);
    Http::assertSentCount(1);
    Notification::assertSentToTimes($this->user, OrderCreatedNotification::class, 1);
});

it('fails the order and returns to checkout when Duitku cannot create the invoice', function () {
    Http::fake(['*/api/merchant/createInvoice' => Http::response(['Message' => 'Merchant not found'], 401)]);

    $this->actingAs($this->user)->from('/courses/lele-galon/checkout')->post('/courses/lele-galon/checkout')
        ->assertRedirect(route('checkout.show', $this->course))
        ->assertSessionHas('error', 'Gagal membuat tagihan pembayaran. Coba lagi.');

    expect(Order::query()->sole()->status)->toBe(OrderStatus::Failed);
    Notification::assertNothingSent();

    fakeInvoice();
    $this->post('/courses/lele-galon/checkout');
    expect(Order::query()->count())->toBe(2);
});

it('sends begin_checkout once when tracking is on', function () {
    Settings::set(['ga4_measurement_id' => 'G-ABC123', 'meta_pixel_id' => '1234567890']);

    $first = $this->actingAs($this->user)->get('/courses/lele-galon/checkout')->getContent();
    $home = $this->get('/')->getContent();

    expect($first)->toContain("gtag('event', 'begin_checkout'")
        ->toContain("fbq('track', 'InitiateCheckout'")
        ->toContain('"value":150000')
        ->toContain('"currency":"IDR"')
        ->and($home)->not->toContain('begin_checkout');
});

it('keeps no tracking events when tracking is off', function () {
    $this->actingAs($this->user)->get('/courses/lele-galon/checkout');

    expect(session('tracking_events'))->toBeNull();
});

it('waits for a parallel checkout to finish its invoice instead of creating a second one', function () {
    Sleep::fake();
    Http::fake();
    $order = app(OrderService::class)->createOrder($this->user, $this->course);
    $lock = Cache::lock('order-invoice:'.$order->id, 30);
    $lock->get();
    Sleep::whenFakingSleep(function () use ($order, $lock) {
        $order->forceFill(['duitku_reference' => 'DSFIRST', 'payment_url' => 'https://app-sandbox.duitku.com/pay/DSFIRST'])->save();
        $lock->release();
    });

    $this->actingAs($this->user)->post('/courses/lele-galon/checkout')->assertRedirect(url('/order/'.$order->uuid.'?pay=1'));

    Http::assertNothingSent();
    expect($order->fresh())->status->toBe(OrderStatus::Pending)->duitku_reference->toBe('DSFIRST');
    Notification::assertNothingSent();
});

it('sends the buyer to the order page when a parallel invoice takes too long', function () {
    Sleep::fake(syncWithCarbon: true);
    Http::fake();
    $order = app(OrderService::class)->createOrder($this->user, $this->course);
    Cache::lock('order-invoice:'.$order->id, 600)->get();

    $this->actingAs($this->user)->post('/courses/lele-galon/checkout')->assertRedirect(url('/order/'.$order->uuid));

    Http::assertNothingSent();
    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});
