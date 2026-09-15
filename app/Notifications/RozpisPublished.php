<?php

namespace App\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a worker their rozpis for a week is out.
 *
 * Deliberately doesn't list which days the recipient is working - assignments can still change
 * after publication (a republish is one click), so a snapshot in the email body would go stale.
 * The action link always points at the live, current state instead.
 */
class RozpisPublished extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly CarbonImmutable $weekStart) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $weekEnd = $this->weekStart->addDays(6);

        return (new MailMessage)
            ->subject('Cine-max zmeny | Rozpis zverejnený')
            ->greeting('Ahoj '.$notifiable->name.'!')
            ->line('Rozpis pre týždeň '.$this->weekStart->format('d.m.').' - '.$weekEnd->format('d.m.Y').' bol zverejnený.')
            ->action('Zobraziť rozpis', route('rozpis.published', ['date' => $this->weekStart->toDateString()]))
            ->line('Rozpis sa ešte môže meniť, preto si ho pred nástupom do práce vždy skontroluj v aplikácii.');
    }
}
