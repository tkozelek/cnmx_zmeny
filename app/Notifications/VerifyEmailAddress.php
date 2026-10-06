<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

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
            ->subject('Cine-max Zmeny | Overenie e-mailovej adresy')
            ->greeting('Dobrý deň,')
            ->line('Kliknite na tlačidlo nižšie a overte svoju e-mailovú adresu.')
            ->action('Overiť e-mail', $url)
            ->line('Odkaz platí '.config('auth.verification.expire', 60).' minút.')
            ->line('Po overení vás ešte musí schváliť manažér kina – dovtedy sa do aplikácie neprihlásite.')
            ->line('Ak ste si účet nevytvorili, tento e-mail ignorujte.')
            ->salutation(new HtmlString('S pozdravom,<br><strong>Cine-max Zmeny</strong>'));
    }
}
