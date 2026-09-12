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
            ->subject('Cine-max zmeny | Žiadosť o zmenu hesla')
            ->greeting('Ahoj!')
            ->line('Zaevidovali sme žiadosť o zmenu hesla pre tvoj účet v Cine-max zmeny.')
            ->action('Resetovať heslo', $url)
            ->line('Tento odkaz má obmedzenú časovú platnosť.')
            ->line('Ak si si túto žiadosť nevyžiadal/a, žiadne ďalšie kroky nie sú potrebné.')
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
