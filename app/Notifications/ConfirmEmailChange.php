<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ConfirmEmailChange extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly string $newEmail,
        private readonly string $url,
    ) {}

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
            ->subject('Cinemax Zmeny | Potvrdenie zmeny e-mailu')
            ->greeting('Ahoj '.$notifiable->name.',')
            ->line('Niekto požiadal o zmenu e-mailovej adresy tvojho účtu na:')
            ->line(new HtmlString('<strong>'.e($this->newEmail).'</strong>'))
            ->action('Skontrolovať zmenu', $this->url)
            ->line('Odkaz platí '.config('auth.verification.expire', 60).' minút. Po potvrdení ešte overíš novú adresu.')
            ->line('Ak o tejto zmene nevieš, na stránke odkazu ju zamietni a zmeň si heslo.')
            ->salutation(new HtmlString('S pozdravom,<br><strong>Cinemax Zmeny</strong>'));
    }
}
