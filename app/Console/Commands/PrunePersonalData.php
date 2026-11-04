<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use App\Services\AccountEraser;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Models\Activity;
use Throwable;

/**
 * Retention as promised on the privacy page (/ochrana-osobnych-udajov): accounts nobody needs any
 * more are deleted together with their sign-ups and absences (cascade).
 *
 * - applicants never approved in any cinema (pending, denied, never verified): 6 months after registering
 * - members gone quiet: 12 months without a login and without a sign-up
 * - log files older than the daily channel's `days` (Monolog's own rotation counts files, not days)
 * - activity log rows (rozpis history, platform admin audit) older than 12 months
 * - expired file-cache entries, e.g. rate-limit keys derived from IPs and e-mails
 *
 * Managers are never pruned here, so a cinema can't lose its last manager to a cron job.
 */
class PrunePersonalData extends Command
{
    protected $signature = 'app:prune-personal-data {--dry-run : Len vypíše, koho by vymazal}';

    protected $description = 'Vymaže neschválených uchádzačov po 6 mesiacoch a neaktívnych členov po 12 mesiacoch';

    public function handle(AccountEraser $eraser): int
    {
        $users = $this->rejectedApplicants()->get()->merge($this->membersGoneQuiet()->get())
            ->reject(fn (User $user): bool => $this->isManagerAnywhere($user));

        $deletedIds = [];

        foreach ($users as $user) {
            $this->line(($this->option('dry-run') ? 'Vymazal by: ' : 'Vymazaný: ')."#{$user->id} {$user->email}");

            if ($this->option('dry-run')) {
                continue;
            }

            // One account that can't be deleted must not keep everyone after it (and the log and
            // activity pruning below) around past what the privacy page promises.
            try {
                $eraser->erase($user);
                $deletedIds[] = $user->id;
            } catch (Throwable $e) {
                report($e);
                $this->error("Nepodarilo sa vymazať #{$user->id}.");
            }
        }

        $this->info("Spolu: {$users->count()}");

        if (! $this->option('dry-run')) {
            Log::info('Retencia: vymazané účty.', ['count' => count($deletedIds), 'user_ids' => $deletedIds]);

            $this->pruneOldLogs();
            $this->pruneOldActivity();
            $this->pruneExpiredCache();
        }

        return self::SUCCESS;
    }

    private function pruneOldLogs(): void
    {
        $cutoff = now()->subDays((int) config('logging.channels.daily.days', 14))->getTimestamp();

        foreach (File::glob(storage_path('logs/*.log')) as $log) {
            if (File::lastModified($log) < $cutoff) {
                File::delete($log);
            }
        }
    }

    private function pruneOldActivity(): void
    {
        Activity::query()->where('created_at', '<', now()->subMonths(12))->delete();
    }

    /** The file store only drops an expired entry when it is read again - most never are. */
    private function pruneExpiredCache(): void
    {
        $directory = storage_path('framework/cache/data');

        if (! File::isDirectory($directory)) {
            return;
        }

        foreach (File::allFiles($directory) as $entry) {
            // FileStore writes the expiry as the first 10 characters (a Unix timestamp).
            $expiresAt = (int) substr((string) file_get_contents($entry->getPathname(), length: 10), 0, 10);

            if ($expiresAt > 0 && $expiresAt < now()->getTimestamp()) {
                File::delete($entry->getPathname());
            }
        }
    }

    /** @return Builder<User> */
    private function rejectedApplicants(): Builder
    {
        return User::query()
            ->where('is_super_admin', false)
            ->whereDoesntHave('teams', fn (Builder $query) => $query->whereNotNull('team_user.approved_at'))
            ->where('created_at', '<', now()->subMonths(6));
    }

    /** @return Builder<User> */
    private function membersGoneQuiet(): Builder
    {
        $cutoff = now()->subMonths(12);

        return User::query()
            ->where('is_super_admin', false)
            ->whereHas('teams', fn (Builder $query) => $query->whereNotNull('team_user.approved_at'))
            ->where('created_at', '<', $cutoff)
            ->where(fn (Builder $query) => $query->whereNull('last_login_at')->orWhere('last_login_at', '<', $cutoff))
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))
                ->from('assignments')
                ->whereColumn('assignments.user_id', 'users.id')
                ->where('date', '>=', $cutoff->toDateString()));
    }

    private function isManagerAnywhere(User $user): bool
    {
        return DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', $user->getMorphClass())
            ->where('model_has_roles.model_id', $user->id)
            ->whereIn('roles.name', [Role::Manager->value, Role::HeadManager->value])
            ->exists();
    }
}
