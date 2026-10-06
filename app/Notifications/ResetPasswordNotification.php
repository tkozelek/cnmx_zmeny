<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class ResetPasswordNotification extends ResetPassword
{
    use Queueable;

    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        parent::__construct($token);
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */

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
            ->subject('Cine-max Zmeny | Zmena hesla')
            ->greeting('Dobrý deň,')
            ->line('Dostali sme žiadosť o zmenu hesla k vášmu účtu v Cine-max Zmeny.')
            ->action('Nastaviť nové heslo', $url)
            ->line('Odkaz platí '.config('auth.passwords.users.expire').' minút.')
            ->line('Ak ste o zmenu nežiadali, tento e-mail ignorujte.')
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
