<?php

namespace App\Listeners;

use App\Logging\Fingerprint;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Events\RoleAttached;
use Spatie\Permission\Events\RoleDetached;
use Spatie\Permission\Models\Role;

/**
 * Auth, role, mail and scheduler events written to the logs - by account number only, never a
 * name or address (see ScrubPersonalData). Auth and roles go to the `security` channel.
 */
class LogApplicationEvents
{
    public function authenticated(Authenticated $event): void
    {
        Context::add('user_id', $event->user->getAuthIdentifier());
    }

    public function login(Login $event): void
    {
        Context::add('user_id', $event->user->getAuthIdentifier());

        Log::channel('security')->info('Prihlásenie.', ['user_id' => $event->user->getAuthIdentifier(), 'guard' => $event->guard, 'remember' => $event->remember]);
    }

    public function failed(Failed $event): void
    {
        Log::channel('security')->warning('Neúspešné prihlásenie.', [
            'user_id' => $event->user?->getAuthIdentifier(),
            'guard' => $event->guard,
            'email_hash' => Fingerprint::of($event->credentials['email'] ?? null),
        ]);
    }

    public function logout(Logout $event): void
    {
        Log::channel('security')->info('Odhlásenie.', ['user_id' => $event->user?->getAuthIdentifier(), 'guard' => $event->guard]);
    }

    public function otherDeviceLogout(OtherDeviceLogout $event): void
    {
        Log::channel('security')->info('Odhlásenie z ostatných zariadení.', ['user_id' => $event->user->getAuthIdentifier()]);
    }

    public function registered(Registered $event): void
    {
        Log::channel('security')->info('Registrácia.', ['user_id' => $event->user->getAuthIdentifier()]);
    }

    public function verified(Verified $event): void
    {
        Log::channel('security')->info('E-mail overený.', ['user_id' => $event->user->getAuthIdentifier()]);
    }

    public function passwordResetLinkSent(PasswordResetLinkSent $event): void
    {
        Log::channel('security')->info('Odoslaný odkaz na obnovu hesla.', ['user_id' => $event->user->getKey()]);
    }

    public function passwordReset(PasswordReset $event): void
    {
        Log::channel('security')->info('Heslo obnovené.', ['user_id' => $event->user->getAuthIdentifier()]);
    }

    public function roleAttached(RoleAttached $event): void
    {
        Log::channel('security')->info('Rola pridaná.', $this->roleContext($event->model->getKey(), $event->rolesOrIds));
    }

    public function roleDetached(RoleDetached $event): void
    {
        Log::channel('security')->info('Rola odobratá.', $this->roleContext($event->model->getKey(), $event->rolesOrIds));
    }

    public function notificationSent(NotificationSent $event): void
    {
        Log::info('Notifikácia odoslaná.', [
            'notification' => $event->notification::class,
            'channel' => $event->channel,
            'notifiable' => $this->describe($event->notifiable),
        ]);
    }

    public function notificationFailed(NotificationFailed $event): void
    {
        Log::error('Notifikáciu sa nepodarilo odoslať.', [
            'notification' => $event->notification::class,
            'channel' => $event->channel,
            'notifiable' => $this->describe($event->notifiable),
        ]);
    }

    public function scheduledTaskFinished(ScheduledTaskFinished $event): void
    {
        if ($event->task->exitCode !== null && $event->task->exitCode !== 0) {
            Log::error('Naplánovaná úloha skončila chybou.', ['task' => $event->task->getSummaryForDisplay(), 'exit_code' => $event->task->exitCode]);
        }
    }

    public function scheduledTaskFailed(ScheduledTaskFailed $event): void
    {
        Log::error('Naplánovaná úloha zlyhala.', ['task' => $event->task->getSummaryForDisplay(), 'exception' => $event->exception]);
    }

    private function describe(mixed $notifiable): string
    {
        return $notifiable instanceof Model ? $notifiable::class.'#'.$notifiable->getKey() : get_debug_type($notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    private function roleContext(mixed $userId, mixed $rolesOrIds): array
    {
        return [
            'user_id' => $userId,
            'team_id' => getPermissionsTeamId(),
            'roles' => Collection::wrap($rolesOrIds)
                ->map(fn (mixed $role): mixed => $role instanceof Role ? $role->name : $role)
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<class-string, string>
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Authenticated::class => 'authenticated',
            Login::class => 'login',
            Failed::class => 'failed',
            Logout::class => 'logout',
            OtherDeviceLogout::class => 'otherDeviceLogout',
            Registered::class => 'registered',
            Verified::class => 'verified',
            PasswordResetLinkSent::class => 'passwordResetLinkSent',
            PasswordReset::class => 'passwordReset',
            RoleAttached::class => 'roleAttached',
            RoleDetached::class => 'roleDetached',
            NotificationSent::class => 'notificationSent',
            NotificationFailed::class => 'notificationFailed',
            ScheduledTaskFinished::class => 'scheduledTaskFinished',
            ScheduledTaskFailed::class => 'scheduledTaskFailed',
        ];
    }
}
