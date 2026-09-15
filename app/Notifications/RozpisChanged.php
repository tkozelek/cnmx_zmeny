<?php

namespace App\Notifications;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a worker their already-published rozpis was edited since.
 *
 * Same "no day list, just a link" reasoning as RozpisPublished - by the time this queued mail
 * sends, further edits may already have landed.
 */
class RozpisChanged extends Notification implements ShouldQueue
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
            ->subject('Cine-max zmeny | Rozpis upravený')
            ->greeting('Ahoj '.$notifiable->name.'!')
            ->line('Rozpis pre týždeň '.$this->weekStart->format('d.m.').' - '.$weekEnd->format('d.m.Y').' bol upravený.')
            ->action('Zobraziť rozpis', route('rozpis.published', ['date' => $this->weekStart->toDateString()]))
            ->line('Skontroluj si prosím, či sa ťa niektorá zmena netýka.');
    }
}
