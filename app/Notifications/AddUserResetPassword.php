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
            ->subject('Cine-max Zmeny | Vytvorenie účtu')
            ->greeting('Dobrý deň,')
            ->line('Manažér kina vám vytvoril účet v aplikácii Cine-max Zmeny.')
            ->line('Registráciu dokončíte tak, že si tlačidlom nižšie nastavíte vlastné heslo. Odkaz platí 24 hodín.')
            ->action('Nastaviť heslo', $url)
            ->line('Ak ste tento účet nečakali, tento e-mail ignorujte.')
            ->salutation(new HtmlString('S pozdravom,<br><strong>Cine-max Zmeny</strong>'));
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
