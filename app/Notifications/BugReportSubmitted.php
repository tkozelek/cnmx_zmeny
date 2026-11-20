<?php

namespace App\Notifications;

use App\Models\BugReport;
use App\Support\SlovakDate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Attachment;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

/** User text goes in as escaped one-line `<p>` blocks, so Markdown in it can't become a link. */
class BugReportSubmitted extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public bool $deleteWhenMissingModels = true;

    /** 15 MB + base64 stays under the usual 25 MB SMTP limit. */
    private const int ATTACHMENT_BUDGET_MB = 15;

    public function __construct(public BugReport $report) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $report = $this->report->loadMissing(['user', 'team']);

        $message = (new MailMessage)
            ->subject('Cinemax Zmeny | Nahlásená chyba #'.$report->id)
            ->replyTo($report->email)
            ->greeting('Nahlásená chyba #'.$report->id)
            ->line($this->detail('Od', $report->user ? "{$report->user->name} {$report->user->lastname} (účet #{$report->user->id})" : 'hosť (bez prihlásenia)'))
            ->line($this->detail('E-mail', $report->email))
            ->line($this->detail('Kino', $report->team->name ?? '–'))
            ->line($this->detail('Stránka', $report->page ?? '–'))
            ->line($this->detail('Odoslané', SlovakDate::dateTime($report->created_at)))
            ->line($this->detail('Prehliadač', $report->user_agent ?? '–'))
            ->line(new HtmlString('<p>'.str_replace(["\r", "\n"], '', nl2br(e($report->description))).'</p>'))
            ->action('Otvoriť v platforme', route('filament.platform.resources.bug-reports.view', ['record' => $report]))
            ->line('Odpoveď na tento e-mail príde priamo nahlasovateľovi.')
            ->salutation(new HtmlString('<strong>Cinemax Zmeny</strong>'));

        $this->attachWithinBudget($message, $report);

        return $message;
    }

    private function attachWithinBudget(MailMessage $message, BugReport $report): void
    {
        $remaining = self::ATTACHMENT_BUDGET_MB * 1024 * 1024;
        $onlyInPanel = [];

        foreach ($report->attachments as $attachment) {
            if ($attachment['size'] > $remaining) {
                $onlyInPanel[] = $attachment['name'];

                continue;
            }

            $remaining -= $attachment['size'];
            $message->attach(
                Attachment::fromStorageDisk(BugReport::DISK, $attachment['path'])
                    ->as($attachment['name'])
                    ->withMime($attachment['mime'])
            );
        }

        if ($onlyInPanel !== []) {
            $message->line($this->detail('Len v platforme (príliš veľké pre e-mail)', implode(', ', $onlyInPanel)));
        }
    }

    private function detail(string $label, string $value): HtmlString
    {
        return new HtmlString('<p><strong>'.$label.':</strong> '.e(str_replace(["\r", "\n"], ' ', $value)).'</p>');
    }
}
