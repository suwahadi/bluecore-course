<?php

namespace App\Notifications;

use App\Notifications\Concerns\BuildsSiteMail;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail implements ShouldQueue
{
    use BuildsSiteMail, Queueable;

    public function toMail($notifiable): MailMessage
    {
        $minutes = config('auth.verification.expire', 60);

        return $this->siteMail($notifiable, 'Verifikasi email Anda')
            ->line('Terima kasih sudah mendaftar. Klik tombol di bawah untuk memastikan email ini milik Anda.')
            ->action('Verifikasi email', $this->verificationUrl($notifiable))
            ->line("Tautan ini berlaku {$minutes} menit.")
            ->line('Jika Anda tidak membuat akun, abaikan email ini.');
    }
}
