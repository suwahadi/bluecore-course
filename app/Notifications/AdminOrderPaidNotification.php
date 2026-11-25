<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Concerns\BuildsSiteMail;
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
        return $this->siteMail($notifiable, 'Pesanan lunas');
    }
}
