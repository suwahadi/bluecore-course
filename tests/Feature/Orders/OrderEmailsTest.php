<?php

use App\Enums\OrderStatus;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AdminOrderPaidNotification;
use App\Notifications\OrderCreatedNotification;
use App\Notifications\OrderExpiredNotification;
use App\Notifications\PaymentSucceededNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

function mailOrder(array $attributes = []): Order
{
    $user = User::factory()->create(['name' => 'Sari Lestari', 'email' => 'sari@contoh.id']);
    $course = Course::factory()->published()->create(['title' => 'Lele Galon', 'slug' => 'lele-galon']);

    return Order::factory()->for($user)->for($course)->create([
        'subtotal' => 150000, 'admin_fee' => 2500, 'amount' => 152500,
        'expires_at' => now()->setDate(2026, 11, 26)->setTime(16, 22), ...$attributes,
    ]);
}

function mailText($message): string
{
    return implode("\n", [...$message->introLines, ...$message->outroLines]);
}

it('tells the buyer the order was created with the full breakdown', function () {
    $order = mailOrder();
    $mail = (new OrderCreatedNotification($order))->toMail($order->user);

    expect($mail->subject)->toBe('Pesanan dibuat')
        ->and(mailText($mail))->toContain($order->order_number)
        ->toContain('Lele Galon')
        ->toContain('Rp 150.000')
        ->toContain('Rp 2.500')
        ->toContain('Rp 152.500')
        ->toContain('Kamis, 26 Nov 2026 16:22')
        ->and($mail->actionText)->toBe('Bayar sekarang')
        ->and($mail->actionUrl)->toBe(route('order.show', $order));
});

it('tells the buyer the payment succeeded', function () {
    $order = mailOrder(['status' => OrderStatus::Paid, 'paid_at' => now()]);
    $mail = (new PaymentSucceededNotification($order))->toMail($order->user);

    expect($mail->subject)->toBe('Pembayaran berhasil')
        ->and(mailText($mail))->toContain($order->order_number)->toContain('Lele Galon')->toContain('Rp 152.500')
        ->and($mail->actionText)->toBe('Mulai belajar')
        ->and($mail->actionUrl)->toBe(url('/learn/lele-galon'));
});

it('tells the buyer the order expired with a way to buy again', function () {
    $order = mailOrder(['status' => OrderStatus::Expired]);
    $mail = (new OrderExpiredNotification($order))->toMail($order->user);

    expect($mail->subject)->toBe('Pesanan kedaluwarsa')
        ->and(mailText($mail))->toContain($order->order_number)->toContain('Lele Galon')
        ->and($mail->actionText)->toBe('Beli lagi')
        ->and($mail->actionUrl)->toBe(route('checkout.show', $order->course));
});

it('tells admins about a paid order with buyer, course, total and method', function () {
    $order = mailOrder(['status' => OrderStatus::Paid, 'paid_at' => now(), 'duitku_payment_code' => 'SP']);
    $admin = User::factory()->admin()->create();
    $mail = (new AdminOrderPaidNotification($order))->toMail($admin);

    expect($mail->subject)->toBe('Pesanan lunas')
        ->and(mailText($mail))->toContain($order->order_number)->toContain('Sari Lestari')->toContain('Lele Galon')->toContain('Rp 152.500')->toContain('ShopeePay')
        ->and($mail->actionText)->toBe('Lihat pesanan')
        ->and($mail->actionUrl)->toBe(route('order.show', $order));
});

it('queues every order email after the commit', function (string $class) {
    $notification = new $class(mailOrder());

    expect($notification)->toBeInstanceOf(ShouldQueue::class)
        ->and($notification->afterCommit)->toBeTrue();
})->with([OrderCreatedNotification::class, PaymentSucceededNotification::class, OrderExpiredNotification::class, AdminOrderPaidNotification::class]);

it('shows a buyer name as plain text in the admin email, never as a link', function () {
    $order = mailOrder(['status' => OrderStatus::Paid, 'paid_at' => now()]);
    $order->user->forceFill(['name' => '[Konfirmasi pembayaran](https://jahat.test) **segera**'])->save();

    $html = (string) (new AdminOrderPaidNotification($order->fresh()))->toMail(User::factory()->admin()->create())->render();

    expect($html)->not->toContain('href="https://jahat.test"')
        ->not->toContain('<strong>segera</strong>')
        ->toContain('Konfirmasi pembayaran');
});
