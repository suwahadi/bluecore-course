<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\BuildsSiteMail;
use App\Support\Format;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSucceededNotification extends Notification implements ShouldQueue
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

        $message = $this->siteMail($notifiable, 'Pembayaran berhasil')
            ->line("Pembayaran pesanan {$order->order_number} sudah kami terima.")
            ->action('Mulai belajar', route('learn.index', $order->course))
            ->line('Akses kursus berlaku selamanya.');

        return $this->summary($message, [
            ['Kursus', $order->course->title],
            ['Total dibayar', Format::rupiah($order->amount), true],
        ]);
    }
}
