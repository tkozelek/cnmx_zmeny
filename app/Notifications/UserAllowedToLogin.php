<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class UserAllowedToLogin extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly User $user) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Cine-max Zmeny | Účet schválený')
            ->greeting('Dobrý deň, '.$this->user->name.',')
            ->line('Manažér kina schválil váš účet. Môžete sa prihlásiť a zapisovať sa na dni.')
            ->action('Prihlásiť sa', route('login'))
            ->line(new HtmlString('Ak potrebujete poradiť, pozrite si sekciu <b>Pomoc</b> v navigácii.'))
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
