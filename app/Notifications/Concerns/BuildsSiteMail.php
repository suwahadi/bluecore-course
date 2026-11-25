<?php

namespace App\Notifications\Concerns;

use App\Support\Settings;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

trait BuildsSiteMail
{
    protected function siteMail(object $notifiable, string $subject, bool $greetByName = true): MailMessage
    {
        $siteName = (string) Settings::get('site_name');
        $message = (new MailMessage)
            ->from((string) config('mail.from.address'), $siteName)
            ->subject($subject)
            ->greeting($greetByName && $this->ownsVerifiedAddress($notifiable) ? new HtmlString('Halo, '.self::plainMarkdown((string) $notifiable->name)) : 'Halo,')
            ->salutation("Salam,  \n".$siteName);

        $replyTo = Settings::get('email');

        return $replyTo ? $message->replyTo($replyTo, $siteName) : $message;
    }

    private function ownsVerifiedAddress(object $notifiable): bool
    {
        return ! $notifiable instanceof MustVerifyEmail || $notifiable->hasVerifiedEmail();
    }

    private static function plainMarkdown(string $text): string
    {
        $entities = preg_replace_callback('/[\\\\`*_\[\]()!|~#<>&]/', fn (array $match) => '&#'.ord($match[0]).';', Str::squish($text));

        return e($entities, false);
    }

    protected function summary(MailMessage $message, array $rows): MailMessage
    {
        $message->viewData['summary'] = $rows;

        return $message;
    }
}
