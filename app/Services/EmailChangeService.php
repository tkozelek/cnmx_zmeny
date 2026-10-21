<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\ConfirmEmailChange;
use App\Notifications\VerifyNewEmail;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/** E-mail change: request (with password) -> old inbox approves -> new inbox verifies. Each request gets a fresh link token. */
class EmailChangeService
{
    public function request(User $user, string $newEmail): void
    {
        $user->forceFill([
            'pending_email' => $newEmail,
            'pending_email_token' => Str::random(40),
            'pending_email_approved_at' => null,
        ])->save();

        $user->notify(new ConfirmEmailChange($newEmail, $this->signedUrl('email-change.show', $user)));
    }

    /** Conditional update, so a double click or a newer request cannot inherit this approval. */
    public function approve(User $user): void
    {
        $approved = User::query()
            ->whereKey($user->getKey())
            ->where('pending_email_token', $user->pending_email_token)
            ->whereNull('pending_email_approved_at')
            ->update(['pending_email_approved_at' => now()]);

        if ($approved === 0) {
            return;
        }

        Notification::route('mail', $user->pending_email)
            ->notify(new VerifyNewEmail($this->signedUrl('email-change.verify', $user)));
    }

    /** False, and the change dropped, when another account took the address meanwhile. */
    public function complete(User $user): bool
    {
        try {
            DB::transaction(function () use ($user) {
                Password::broker('users')->deleteToken($user);
                Password::broker('add_user')->deleteToken($user);

                $user->forceFill([
                    'email' => $user->pending_email,
                    'email_verified_at' => now(),
                    'pending_email' => null,
                    'pending_email_token' => null,
                    'pending_email_approved_at' => null,
                ])->save();
            });
        } catch (UniqueConstraintViolationException) {
            $this->cancel($user->refresh());

            return false;
        }

        return true;
    }

    public function cancel(User $user): void
    {
        $user->forceFill([
            'pending_email' => null,
            'pending_email_token' => null,
            'pending_email_approved_at' => null,
        ])->save();
    }

    public function isPending(User $user, string $token): bool
    {
        return $user->pending_email_token !== null
            && hash_equals($user->pending_email_token, $token);
    }

    private function signedUrl(string $route, User $user): string
    {
        return URL::temporarySignedRoute(
            $route,
            now()->addMinutes(config('auth.verification.expire', 60)),
            ['id' => $user->getKey(), 'token' => $user->pending_email_token],
        );
    }
}
