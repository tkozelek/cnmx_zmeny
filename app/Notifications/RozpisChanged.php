<?php

namespace App\Notifications;

use App\Support\SlovakDate;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

/**
 * Tells a worker their already-published rozpis was edited since.
 *
 * Same "no day list, just a link" reasoning as RozpisPublished - by the time this queued mail
 * sends, further edits may already have landed.
 */
class RozpisChanged extends Notification implements ShouldQueue
{
    use Queueable;

    /** An SMTP hiccup must not lose a verification or reset link for good. */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

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
            ->subject('Cinemax Zmeny | Rozpis upravený')
            ->greeting('Ahoj '.$notifiable->name.',')
            ->line('Rozpis pre týždeň '.SlovakDate::range($this->weekStart, $weekEnd).' bol upravený.')
            ->action('Zobraziť rozpis', route('rozpis.published', ['date' => $this->weekStart->toDateString()]))
            ->line('Skontroluj si prosím, či sa ťa niektorá zmena netýka.')
            ->salutation(new HtmlString('S pozdravom,<br><strong>Cinemax Zmeny</strong>'));
    }
}
