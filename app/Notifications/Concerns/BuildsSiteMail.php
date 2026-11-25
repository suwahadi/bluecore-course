<?php

namespace App\Notifications\Concerns;

use App\Support\Settings;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

trait BuildsSiteMail
{
    private static string $markdownCharacters = '\`*_[]()!|~#';

    protected function siteMail(object $notifiable, string $subject, bool $greetByName = true): MailMessage
    {
        $siteName = (string) Settings::get('site_name');
        $message = (new MailMessage)
            ->from((string) config('mail.from.address'), $siteName)
            ->subject($subject)
            ->greeting($greetByName ? 'Halo, '.addcslashes(Str::squish((string) $notifiable->name), self::$markdownCharacters) : 'Halo,')
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
