<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Laravel's verification mail, in Slovak and off the request thread.
 *
 * Queued like {@see UserAllowedToLogin}: registration must not sit waiting on SMTP. With
 * QUEUE_CONNECTION=sync that is still inline - it starts paying off the moment a real driver
 * and a worker exist.
 */
class VerifyEmailAddress extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Overenie e-mailovej adresy')
            ->line('Klikni na tlačidlo nižšie a over svoju e-mailovú adresu.')
            ->action('Overiť e-mail', $url)
            ->line('Po overení ťa ešte musí schváliť vedúci kina - dovtedy sa do aplikácie neprihlásiš.')
            ->line('Ak si účet nevytváral/a ty, tento e-mail ignoruj.');
    }
}
