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
 * Tells a worker their rozpis for a week is out.
 *
 * Deliberately doesn't list which days the recipient is working - assignments can still change
 * after publication (a republish is one click), so a snapshot in the email body would go stale.
 * The action link always points at the live, current state instead.
 */
class RozpisPublished extends Notification implements ShouldQueue
{
    use Queueable;

    /** An SMTP hiccup must not lose a verification or reset link for good. */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public function __construct(private readonly CarbonImmutable $weekStart, private readonly string $teamName) {}

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
            ->subject('Cinemax Zmeny | Rozpis zverejnený – '.$this->teamName)
            ->greeting('Ahoj '.$notifiable->name.',')
            ->line('Rozpis kina '.$this->teamName.' pre týždeň '.SlovakDate::range($this->weekStart, $weekEnd).' bol zverejnený.')
            ->action('Zobraziť rozpis', route('rozpis.published', ['date' => $this->weekStart->toDateString()]))
            ->line('Rozpis sa ešte môže meniť, preto si ho pred nástupom do práce vždy skontroluj v aplikácii.')
            ->salutation(new HtmlString('S pozdravom,<br><strong>Cinemax Zmeny</strong>'));
    }
}
