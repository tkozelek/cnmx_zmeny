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

    /** An SMTP hiccup must not lose a verification or reset link for good. */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly User $user, private readonly string $teamName) {}

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
            ->subject('Cinemax Zmeny | Účet schválený – '.$this->teamName)
            ->greeting('Ahoj '.$this->user->name.',')
            ->line('Manažér kina '.$this->teamName.' schválil tvoj účet. Môžeš sa prihlásiť a zapisovať sa na dni.')
            ->action('Prihlásiť sa', route('login'))
            ->line(new HtmlString('Ak potrebuješ poradiť, pozri si sekciu <b>Pomoc</b> v navigácii.'))
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
