<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

/** Queued: sent inline, the reply for a registered address took noticeably longer than for an unknown one. */
class ResetPasswordNotification extends ResetPassword implements ShouldQueue
{
    use Queueable;

    /** An SMTP hiccup must not lose a verification or reset link for good. */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300];

    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        parent::__construct($token);
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
            ->subject('Cinemax Zmeny | Zmena hesla')
            ->greeting('Ahoj,')
            ->line('Dostali sme žiadosť o zmenu hesla k tvojmu účtu v Cinemax Zmeny.')
            ->action('Nastaviť nové heslo', $url)
            ->line('Odkaz platí '.config('auth.passwords.users.expire').' minút.')
            ->line('Ak o tejto žiadosti nevieš, tento e-mail ignoruj.')
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
