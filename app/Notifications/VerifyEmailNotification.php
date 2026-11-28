<?php

namespace App\Notifications;

use App\Notifications\Concerns\BuildsSiteMail;
use App\Support\Settings;
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

        return $this->siteMail($notifiable, 'Verifikasi emailmu', greetByName: false)
            ->line('Klik tombol di bawah untuk memastikan email ini milikmu.')
            ->action('Verifikasi email', $this->verificationUrl($notifiable))
            ->line("Tautan ini berlaku {$minutes} menit.")
            ->line('Jika kamu tidak mendaftar atau tidak mengganti email di '.Settings::get('site_name').', abaikan email ini.');
    }
}
