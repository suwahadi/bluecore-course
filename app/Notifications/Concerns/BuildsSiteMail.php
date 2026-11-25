<?php

namespace App\Notifications\Concerns;

use App\Support\Settings;
use Illuminate\Notifications\Messages\MailMessage;

trait BuildsSiteMail
{
    protected function siteMail(object $notifiable, string $subject): MailMessage
    {
        $siteName = (string) Settings::get('site_name');
        $message = (new MailMessage)
            ->from((string) config('mail.from.address'), $siteName)
            ->subject($subject)
            ->greeting('Halo, '.$notifiable->name)
            ->salutation("Salam,  \n".$siteName);

        $replyTo = Settings::get('email');

        return $replyTo ? $message->replyTo($replyTo, $siteName) : $message;
    }

    protected function summary(MailMessage $message, array $rows): MailMessage
    {
        $message->viewData['summary'] = $rows;

        return $message;
    }
}
