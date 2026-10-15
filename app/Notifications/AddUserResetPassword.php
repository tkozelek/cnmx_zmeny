<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\HtmlString;

class AddUserResetPassword extends ResetPassword
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        parent::__construct($token);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Cinemax Zmeny | Vytvorenie účtu')
            ->greeting('Ahoj,')
            ->line('Manažér kina ti vytvoril účet v aplikácii Cinemax Zmeny.')
            ->line('Registráciu dokončíš tak, že si tlačidlom nižšie nastavíš vlastné heslo. Odkaz platí 24 hodín.')
            ->action('Nastaviť heslo', $url)
            ->line('Ak tento e-mail nečakáš, pokojne ho ignoruj.')
            ->salutation(new HtmlString('S pozdravom,<br><strong>Cinemax Zmeny</strong>'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
