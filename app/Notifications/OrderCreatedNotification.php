<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\BuildsSiteMail;
use App\Support\Format;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification implements ShouldQueue
{
    use BuildsSiteMail, Queueable;

    public function __construct(public Order $order)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('course:id,title,slug');

        return $this->siteMail($notifiable, 'Pesanan dibuat')
            ->line("Pesanan {$order->order_number} untuk kursus {$order->course->title} sudah dibuat.")
            ->line('Harga kursus: '.Format::rupiah($order->subtotal))
            ->line('Biaya admin: '.Format::rupiah($order->admin_fee))
            ->line('Total: '.Format::rupiah($order->amount))
            ->line('Bayar sebelum '.Format::datetime($order->expires_at).'. Akses kursus terbuka otomatis setelah pembayaran diterima.')
            ->action('Bayar sekarang', route('order.show', $order));
    }
}
