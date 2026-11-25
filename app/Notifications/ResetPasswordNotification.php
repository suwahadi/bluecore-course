<?php

namespace App\Notifications;

use App\Notifications\Concerns\BuildsSiteMail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use BuildsSiteMail, Queueable;

    public function toMail($notifiable): MailMessage
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return $this->siteMail($notifiable, 'Atur ulang kata sandi', greetByName: false)
            ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun Anda.')
            ->action('Atur ulang kata sandi', $this->resetUrl($notifiable))
            ->line("Tautan ini berlaku {$minutes} menit.")
            ->line('Jika Anda tidak meminta atur ulang kata sandi, abaikan email ini. Kata sandi Anda tidak berubah.');
    }
}
