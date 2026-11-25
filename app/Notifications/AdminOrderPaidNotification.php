<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\BuildsSiteMail;
use App\Services\Payments\DuitkuClient;
use App\Support\Format;
use App\Support\MailText;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminOrderPaidNotification extends Notification implements ShouldQueue
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
        $order = $this->order->loadMissing(['course:id,title,slug', 'user:id,name,email']);
        $method = app(DuitkuClient::class)->paymentName($order->duitku_payment_code) ?? '-';

        return $this->siteMail($notifiable, 'Pesanan lunas')
            ->line("Pesanan {$order->order_number} sudah lunas lewat Duitku.")
            ->line('Pembeli: '.MailText::plain($order->user->name).' ('.MailText::plain($order->user->email).')')
            ->line("Kursus: {$order->course->title}")
            ->line('Total: '.Format::rupiah($order->amount))
            ->line("Metode bayar: {$method}")
            ->action('Lihat pesanan', route('order.show', $order));
    }
}
