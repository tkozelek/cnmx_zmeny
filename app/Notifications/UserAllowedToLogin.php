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
            ->subject('Cine-max zmeny | Bol si overený')
            ->greeting('Ahoj '.$this->user->name)
            ->line('Tvoj účet bol overený! Od tohto momentu sa môžeš prihlásiť do aplikácie.')
            ->line(new HtmlString('V prípade že potrebuješ viac informácií, neváhaj využiť sekciu <b>POMOC</b> v navigácií.'))
            ->action('Prihlásiť sa', route('login'))
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
