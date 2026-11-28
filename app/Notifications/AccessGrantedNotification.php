<?php

namespace App\Notifications;

use App\Models\Course;
use App\Notifications\Concerns\BuildsSiteMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccessGrantedNotification extends Notification implements ShouldQueue
{
    use BuildsSiteMail, Queueable;

    public function __construct(public Course $course)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->siteMail($notifiable, 'Akses kursus diberikan')
            ->line("Kamu sekarang punya akses ke kursus {$this->course->title}.")
            ->action('Mulai belajar', route('learn.index', $this->course))
            ->line('Akses ini berlaku selamanya selama akunmu aktif.');
    }
}
