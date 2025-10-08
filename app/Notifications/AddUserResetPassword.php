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
            ->subject('Cine-max zmeny | Bol ti vytvorený účet')
            ->greeting('Ahoj!')
            ->line('Tento e-mail ti prišiel, lebo vedenie prevádzky ti vygenerovalo účet.')
            ->line('Prosíme ťa, aby si si zmenil heslo čo najskôr. Platnosť e-mailu je 24 hodín.')
            ->action('Zmeniť heslo', $url)
            ->line('Tento odkaz ma krátku časovú platnosť. (24 hodín)')
            ->line('Ak si si túto žiadosť nevyžiadal, nemusíš nič robiť.')
            ->salutation(new HtmlString('S pozdravom, <br>'.config('app.name').' '));
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
