<?php

namespace App\Console\Commands;

use App\Enums\AbsenceStatus;
use App\Enums\Role as RoleEnum;
use App\Models\Absence;
use App\Models\Assignment;
use App\Models\Media;
use App\Models\PositionSlot;
use App\Models\Team;
use App\Models\User;
use App\Models\WeekLock;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * One-off import from the legacy production schema (see the `legacy` connection in
 * config/database.php - LEGACY_DB_* in .env) into the rewritten schema. Replaces the target
 * team's seeded/demo data with the real one.
 *
 * Read-only against `legacy`: every call against that connection is a SELECT. This is what
 * makes it safe to point LEGACY_DB_* at a real production database and import prod -> whatever
 * DB_* targets (e.g. beta) without touching prod - pair it with a read-only DB user there too.
 *
 * Not migrated - the new schema has no destination for them:
 * - `shifts` / `rates` (evidencia hodín was dropped from the app entirely)
 * - `bugs`, `file_storage` (bug reporting and per-week file uploads were scrapped in the rewrite)
 */
class ImportLegacyData extends Command
{
    protected $signature = 'app:import-legacy-data
        {--team=1 : ID tímu, ktorý dostane reálne dáta}
        {--force : Preskočiť potvrdzovaciu otázku}';

    protected $description = 'Nahradí demo dáta daného tímu reálnymi dátami z legacy databázy (LEGACY_DB_* v .env)';

    public function handle(): int
    {
        $legacy = DB::connection('legacy');

        try {
            $legacy->getPdo();
        } catch (Throwable $e) {
            $this->error("Nepodarilo sa pripojiť na legacy databázu: {$e->getMessage()}");

            return self::FAILURE;
        }

        if (! Schema::connection('legacy')->hasTable('users')) {
            $this->error('Legacy databáza neobsahuje tabuľku "users" - skontroluj LEGACY_DB_* v .env.');

            return self::FAILURE;
        }

        $team = Team::find((int) $this->option('team'));

        if (! $team) {
            $this->error("Tím s ID {$this->option('team')} neexistuje.");

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm(
            "Toto vymaže demo dáta tímu '{$team->name}' (zamestnancov, rozpisy, absencie) a nahradí ich reálnymi dátami. Pokračovať?"
        )) {
            return self::SUCCESS;
        }

        DB::transaction(function () use ($team, $legacy): void {
            setPermissionsTeamId($team->id);

            // A few thousand historical rows landing in the activity log would bury the real
            // edits the rozpis history panel exists to show - same reasoning as SampleAssignmentsSeeder.
            activity()->withoutLogging(function () use ($team, $legacy): void {
                $this->clearSeededData($team, $legacy);
                $userMap = $this->importUsers($team, $legacy);
                $this->importAssignments($legacy, $userMap);
                $this->importAbsences($legacy, $userMap);
            });
        });

        $this->info('Import dokončený.');
        $this->line('Preskočené (nová schéma pre ne nemá tabuľku): shifts, rates, bugs, file_storage.');

        return self::SUCCESS;
    }

    /**
     * Wipes this team's plan data outright (scoped by the setPermissionsTeamId() global scope,
     * see BelongsToTeam), then drops every member who has no counterpart in the legacy data -
     * unless they also belong to another team, in which case only this team's membership goes.
     */
    private function clearSeededData(Team $team, Connection $legacy): void
    {
        Media::query()->delete();
        WeekLock::query()->delete();
        Assignment::query()->delete();
        Absence::query()->delete();
        PositionSlot::query()->delete();

        $legacyEmails = $legacy->table('users')->pluck('email');

        $demoUsers = $team->users()->whereNotIn('users.email', $legacyEmails)->get();

        foreach ($demoUsers as $user) {
            $user->teams()->detach($team->id);
            DB::table('model_has_roles')->where('team_id', $team->id)->where('model_id', $user->id)->delete();
            DB::table('model_has_permissions')->where('team_id', $team->id)->where('model_id', $user->id)->delete();

            if ($user->teams()->count() === 0) {
                $user->delete();
            }
        }
    }

    /**
     * Legacy id_role: 1 neoverený, 2 brigádnik, 3 administrátor, 4 zablokovaný. Only 2 and 3
     * are actually in use in the restored data, so unverified/blocked are not handled here -
     * see App\Enums\Role and _planning/20-schema-rework-handoff.md for where those states moved to.
     *
     * @return array<int, int> legacy user id => new user id
     */
    private function importUsers(Team $team, Connection $legacy): array
    {
        $map = [];

        foreach ($legacy->table('users')->get() as $legacyUser) {
            $user = User::updateOrCreate(
                ['email' => $legacyUser->email],
                [
                    'name' => $legacyUser->name,
                    'lastname' => $legacyUser->lastname,
                    'password' => $legacyUser->password,
                    'current_team_id' => $team->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'last_login_at' => $legacyUser->last_login_at,
                ]
            );

            $user->teams()->syncWithoutDetaching([$team->id => ['approved_at' => $legacyUser->created_at]]);

            $role = (int) $legacyUser->id_role === 3 ? RoleEnum::HeadManager : RoleEnum::Employee;
            $user->syncRoles([$role->value]);

            $map[$legacyUser->id] = $user->id;
        }

        return $map;
    }

    /**
     * user_days -> assignments. `days` is only ever consulted here to resolve `id_day` to an
     * actual date - resolved in PHP rather than a SQL join, since `legacy` may be a genuinely
     * separate database server that a cross-table join can't reach.
     *
     * position_id is left null: legacy signup never chose a position either (popis is a free-text
     * time note, not a position code - confirmed against the restored data), so this matches the
     * "employee signs up for a day, admin fills in the position later" model exactly.
     *
     * @param  array<int, int>  $userMap
     */
    private function importAssignments(Connection $legacy, array $userMap): void
    {
        $dayDates = $legacy->table('days')->pluck('date', 'id');

        $rows = $legacy->table('user_days')->get(['id_user', 'id_day', 'popis']);

        $now = now();
        $insert = [];

        foreach ($rows as $row) {
            $userId = $userMap[$row->id_user] ?? null;
            $date = $dayDates[$row->id_day] ?? null;

            if (! $userId || ! $date) {
                continue;
            }

            $insert[] = [
                'team_id' => getPermissionsTeamId(),
                'user_id' => $userId,
                'position_id' => null,
                'date' => $date,
                'note' => $row->popis !== null ? mb_substr(trim($row->popis), 0, 255) : null,
                'created_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // insertOrIgnore: legacy user_days has no unique (user, day) constraint, so a handful
        // of duplicate signups are silently dropped rather than failing the whole import.
        foreach (array_chunk($insert, 500) as $chunk) {
            DB::table('assignments')->insertOrIgnore($chunk);
        }
    }

    /**
     * user_holidays -> absences. A cancelled legacy holiday (date_canceled set) becomes a
     * cancelled absence rather than being dropped, so the history stays visible.
     *
     * @param  array<int, int>  $userMap
     */
    private function importAbsences(Connection $legacy, array $userMap): void
    {
        $rows = $legacy->table('user_holidays')->get();

        $now = now();
        $insert = [];

        foreach ($rows as $row) {
            $userId = $userMap[$row->id_user] ?? null;

            if (! $userId) {
                continue;
            }

            $insert[] = [
                'team_id' => getPermissionsTeamId(),
                'user_id' => $userId,
                'date_from' => Carbon::parse($row->date_from)->toDateString(),
                'date_to' => Carbon::parse($row->date_to)->toDateString(),
                'day_of_week' => null,
                'reason' => $row->popis !== null ? mb_substr($row->popis, 0, 500) : null,
                'status' => $row->date_canceled !== null ? AbsenceStatus::Cancelled->value : AbsenceStatus::Active->value,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($insert, 500) as $chunk) {
            DB::table('absences')->insert($chunk);
        }
    }
}
