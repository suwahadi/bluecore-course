<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\BuildsSiteMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderExpiredNotification extends Notification implements ShouldQueue
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

        return $this->siteMail($notifiable, 'Pesanan kedaluwarsa')
            ->line("Pesanan {$order->order_number} untuk kursus {$order->course->title} tidak dibayar sampai batas waktu, jadi sudah kedaluwarsa.")
            ->line('Jika masih ingin belajar, buat pesanan baru lewat tombol di bawah.')
            ->action('Beli lagi', route('checkout.show', $order->course));
    }
}
