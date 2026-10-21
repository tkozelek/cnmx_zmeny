<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class VerifyNewEmail extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly string $url) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Cinemax Zmeny | Overenie novej e-mailovej adresy')
            ->greeting('Ahoj,')
            ->line('Klikni na tlačidlo nižšie a dokonči zmenu e-mailovej adresy svojho účtu.')
            ->action('Overiť novú adresu', $this->url)
            ->line('Odkaz platí '.config('auth.verification.expire', 60).' minút. Odvtedy sa budeš prihlasovať touto adresou.')
            ->line('Ak tento e-mail nečakáš, pokojne ho ignoruj.')
            ->salutation(new HtmlString('S pozdravom,<br><strong>Cinemax Zmeny</strong>'));
    }
}
