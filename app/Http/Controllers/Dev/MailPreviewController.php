<?php

namespace App\Http\Controllers\Dev;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AccessGrantedNotification;
use App\Notifications\AdminOrderPaidNotification;
use App\Notifications\OrderCreatedNotification;
use App\Notifications\OrderExpiredNotification;
use App\Notifications\PaymentSucceededNotification;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\View\View;

class MailPreviewController extends Controller
{
    public function index(): View
    {
        return view('dev.mail-preview', ['previews' => self::previews()]);
    }

    public function show(string $template): MailMessage
    {
        $preview = self::previews()[$template] ?? abort(404);

        return $preview['message']();
    }

    public static function previews(): array
    {
        $buyer = (new User)->forceFill(['id' => 1, 'name' => 'Sari Lestari', 'email' => 'sari@contoh.id', 'email_verified_at' => now()]);
        $admin = (new User)->forceFill(['id' => 2, 'name' => 'Admin Bluecore', 'email' => 'admin@contoh.id', 'email_verified_at' => now()]);
        $course = (new Course)->forceFill(['id' => 1, 'title' => 'Budidaya Lele dalam Galon', 'slug' => 'budidaya-lele-dalam-galon']);
        $order = fn (OrderStatus $status) => (new Order)->forceFill([
            'id' => 1,
            'uuid' => '9f3c2a10-5b7e-4c1d-8a2f-0e6b4d3c2a10',
            'order_number' => 'UBTEWUELFYL',
            'subtotal' => 149000,
            'admin_fee' => 2500,
            'amount' => 151500,
            'status' => $status,
            'expires_at' => now()->addDay(),
            'paid_at' => $status === OrderStatus::Paid ? now() : null,
            'duitku_payment_code' => $status === OrderStatus::Paid ? 'SP' : null,
        ])->setRelation('course', $course)->setRelation('user', $buyer);

        return [
            'verify' => ['label' => 'Verifikasi email', 'message' => fn () => (new VerifyEmailNotification)->toMail($buyer)],
            'reset' => ['label' => 'Atur ulang kata sandi', 'message' => fn () => (new ResetPasswordNotification('contoh-token'))->toMail($buyer)],
            'order-created' => ['label' => 'Pesanan dibuat', 'message' => fn () => (new OrderCreatedNotification($order(OrderStatus::Pending)))->toMail($buyer)],
            'payment-succeeded' => ['label' => 'Pembayaran berhasil', 'message' => fn () => (new PaymentSucceededNotification($order(OrderStatus::Paid)))->toMail($buyer)],
            'order-expired' => ['label' => 'Pesanan kedaluwarsa', 'message' => fn () => (new OrderExpiredNotification($order(OrderStatus::Expired)))->toMail($buyer)],
            'access-granted' => ['label' => 'Akses kursus diberikan', 'message' => fn () => (new AccessGrantedNotification($course))->toMail($buyer)],
            'admin-order-paid' => ['label' => 'Pesanan lunas (untuk admin)', 'message' => fn () => (new AdminOrderPaidNotification($order(OrderStatus::Paid)))->toMail($admin)],
        ];
    }
}
