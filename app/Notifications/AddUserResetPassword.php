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
            ->subject('Cine-max zmeny | Vytvorenie účtu')
            ->greeting('Ahoj!')
            ->line('Vedenie prevádzky ti vytvorilo účet v aplikácii Cine-max zmeny.')
            ->line('Pre dokončenie registrácie si nastav vlastné heslo kliknutím na tlačidlo nižšie. Platnosť odkazu je 24 hodín.')
            ->action('Nastaviť heslo', $url)
            ->line('Ak si tento účet neočakával/a, tento e-mail môžeš ignorovať.')
            ->salutation(new HtmlString('S pozdravom,<br><strong>'.config('app.name').'</strong>'));
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
