<?php

use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AdminOrderPaidNotification;
use App\Notifications\PaymentSucceededNotification;
use App\Services\Orders\OrderService;
use App\Support\Settings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
    $this->service = app(OrderService::class);
    $this->user = User::factory()->create();
    $this->course = Course::factory()->published()->create(['price' => 150000]);
});

it('creates one pending order with the admin fee snapshot', function () {
    Settings::set(['admin_fee' => 2500]);
    $this->travelTo(now()->setTime(10, 0));

    $order = $this->service->createOrder($this->user, $this->course);

    expect($order)->status->toBe(OrderStatus::Pending)->subtotal->toBe(150000)->admin_fee->toBe(2500)->amount->toBe(152500)
        ->and($order->expires_at->equalTo(now()->addMinutes(config('duitku.expiry_minutes'))))->toBeTrue()
        ->and($order->uuid)->not->toBeEmpty()
        ->and($order->order_number)->toMatch('/^[A-Z0-9]{11}$/');

    Settings::set(['admin_fee' => 9999]);
    expect($this->service->createOrder($this->user, $this->course)->id)->toBe($order->id)
        ->and($order->fresh()->admin_fee)->toBe(2500)
        ->and(Order::query()->count())->toBe(1);
});

it('starts a new order when the open one has run out of time', function () {
    $old = Order::factory()->for($this->user)->for($this->course)->create(['expires_at' => now()->subMinute()]);

    expect($this->service->createOrder($this->user, $this->course)->id)->not->toBe($old->id);
});

it('creates no order for a course the user already owns', function () {
    Enrollment::factory()->for($this->user)->for($this->course)->create();

    expect($this->service->createOrder($this->user, $this->course))->toBeNull()
        ->and(Order::query()->count())->toBe(0);
});

it('marks an order paid once, grants access and sends both emails', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->admin()->unverified()->create();
    $order = Order::factory()->for($this->user)->for($this->course)->create();

    expect($this->service->markPaid($order, 'SP', 'DS1'))->toBeTrue()
        ->and($this->service->markPaid($order, 'SP', 'DS1'))->toBeFalse();

    expect($order->fresh())->status->toBe(OrderStatus::Paid)->duitku_payment_code->toBe('SP')->duitku_reference->toBe('DS1')
        ->and($order->fresh()->paid_at)->not->toBeNull()
        ->and(Enrollment::query()->where(['user_id' => $this->user->id, 'course_id' => $this->course->id, 'order_id' => $order->id])->count())->toBe(1);
    Notification::assertSentToTimes($this->user, PaymentSucceededNotification::class, 1);
    Notification::assertSentTo($admin, AdminOrderPaidNotification::class);
    Notification::assertCount(2);
});

it('records an admin override without telling the admins', function () {
    Log::spy();
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->for($this->user)->for($this->course)->create(['status' => OrderStatus::Expired]);

    $this->service->markPaid($order, null, null, $admin, 'Transfer BCA 25 Nov');

    expect($order->fresh())->status->toBe(OrderStatus::Paid)->confirmed_by_user_id->toBe($admin->id)->paid_note->toBe('Transfer BCA 25 Nov');
    Notification::assertSentTo($this->user, PaymentSucceededNotification::class);
    Notification::assertNotSentTo($admin, AdminOrderPaidNotification::class);
    Log::shouldHaveReceived('info')->withArgs(fn ($message, $context) => $message === 'Admin marked order paid' && $context['admin_id'] === $admin->id);
});

it('never pays a refunded order again', function () {
    $order = Order::factory()->for($this->user)->for($this->course)->create(['status' => OrderStatus::Refunded]);

    expect($this->service->markPaid($order, 'SP', 'DS1'))->toBeFalse()
        ->and($order->fresh()->status)->toBe(OrderStatus::Refunded);
    Notification::assertNothingSent();
});

it('cancels, expires and fails only pending orders', function () {
    $pending = fn () => Order::factory()->for($this->user)->for($this->course)->create(['expires_at' => now()->subMinute()]);
    $paid = Order::factory()->paid()->for($this->user)->for($this->course)->create();

    expect($this->service->cancel($order = $pending()))->toBeTrue()->and($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($this->service->expire($order = $pending()))->toBeTrue()->and($order->fresh()->status)->toBe(OrderStatus::Expired)
        ->and($this->service->fail($order = $pending()))->toBeTrue()->and($order->fresh()->status)->toBe(OrderStatus::Failed)
        ->and($this->service->cancel($paid))->toBeFalse()
        ->and($this->service->expire($paid))->toBeFalse()
        ->and($this->service->fail($paid))->toBeFalse()
        ->and($paid->fresh()->status)->toBe(OrderStatus::Paid);
});

it('does not expire an order that still has time', function () {
    $order = Order::factory()->for($this->user)->for($this->course)->create(['expires_at' => now()->addHour()]);

    expect($this->service->expire($order))->toBeFalse()->and($order->fresh()->status)->toBe(OrderStatus::Pending);
});

it('records a refund and closes only the access this order gave', function () {
    Log::spy();
    $admin = User::factory()->admin()->create();
    $order = Order::factory()->for($this->user)->for($this->course)->create();
    $this->service->markPaid($order, 'SP', 'DS1');
    $manualCourse = Course::factory()->create();
    Enrollment::factory()->for($this->user)->for($manualCourse)->create(['granted_by_user_id' => $admin->id]);

    expect($this->service->refund($order->fresh(), $admin, 'Transfer balik BCA'))->toBeTrue()
        ->and($this->service->refund($order->fresh(), $admin, 'Lagi'))->toBeFalse();

    expect($order->fresh())->status->toBe(OrderStatus::Refunded)->refunded_by_user_id->toBe($admin->id)->refund_note->toBe('Transfer balik BCA')
        ->and($order->fresh()->refunded_at)->not->toBeNull()
        ->and(Enrollment::query()->where('order_id', $order->id)->count())->toBe(0)
        ->and(Enrollment::query()->where('course_id', $manualCourse->id)->count())->toBe(1);
    Log::shouldHaveReceived('info')->withArgs(fn ($message, $context) => $message === 'Admin recorded refund' && $context['order_number'] === $order->order_number);
});

it('lets only owners and admins view an order', function () {
    $order = Order::factory()->for($this->user)->for($this->course)->create();
    $stranger = User::factory()->create();
    $admin = User::factory()->admin()->create();

    expect($this->user->can('view', $order))->toBeTrue()
        ->and($admin->can('view', $order))->toBeTrue()
        ->and($stranger->can('view', $order))->toBeFalse();
});

it('moves access to another paid order of the same course instead of closing it on refund', function () {
    $admin = User::factory()->admin()->create();
    $first = Order::factory()->for($this->user)->for($this->course)->create();
    $second = Order::factory()->for($this->user)->for($this->course)->create();
    $this->service->markPaid($second, 'SP', 'DS2');
    $this->service->markPaid($first, 'SP', 'DS1');

    expect(Enrollment::query()->sole()->order_id)->toBe($second->id);

    $this->service->refund($second->fresh(), $admin, 'Bayar dua kali');

    expect(Enrollment::query()->sole()->order_id)->toBe($first->id);
});
