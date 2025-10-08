<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserAllowedToLoginOld extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function via()
    {
        return ['mail'];
    }

    public function build()
    {
        return $this
            ->from('mailer@cinemaxzmeny.eu', 'Mailer')
            ->subject('Cine-max zmeny | Tvoj účet bol overený')
            ->view('emails.user_allowed_to_login')
            ->with(['user' => $this->user]);
    }
}
