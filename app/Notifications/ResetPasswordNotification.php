<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
            ->line('Tento e-mail ti prišiel lebo sme obdržali žiadosť o zmene hesla pre tvoj účet.')
            ->action('Resetovať heslo', $url)
            ->line('Tento odkaz ma krátku časovú platnosť.')
            ->line('Ak si si túto žiadosť nevyžiadal, nemusíš nič robiť.')
            ->salutation(new HtmlString("S pozdravom, <br>".config('app.name')." "));
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
