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

    /** An SMTP hiccup must not lose a verification or reset link for good. */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    protected function buildMailMessage($url): MailMessage
    {
        return (new MailMessage)
            ->subject('Cinemax Zmeny | Overenie e-mailovej adresy')
            ->greeting('Ahoj,')
            ->line('Klikni na tlačidlo nižšie a over svoju e-mailovú adresu.')
            ->action('Overiť e-mail', $url)
            ->line('Odkaz platí '.config('auth.verification.expire', 60).' minút.')
            ->line('Po overení ťa ešte musí schváliť manažér kina – dovtedy sa do aplikácie neprihlásiš.')
            ->line('Ak tento e-mail nečakáš, pokojne ho ignoruj.')
            ->salutation(new HtmlString('S pozdravom,<br><strong>Cinemax Zmeny</strong>'));
    }
}
