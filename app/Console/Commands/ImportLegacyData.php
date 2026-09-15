<?php

namespace App\Console\Commands;

use App\Enums\AbsenceStatus;
use App\Enums\Role as RoleEnum;
use App\Models\Absence;
use App\Models\Assignment;
use App\Models\Media;
use App\Models\Team;
use App\Models\User;
use App\Models\WeekLock;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Repeatable import from the legacy production schema (see the `legacy` connection in
 * config/database.php - LEGACY_DB_* in .env) into the rewritten schema. Safe to cron: users
 * and absences are fully replaced from legacy, but assignments are diff-merged rather than
 * wiped, so a position placement made in the rozpis builder (position_id/position_slot_id/
 * start_time/end_time - set by RozpisDay::place() onto the same assignment row) survives a
 * re-run as long as the person is still signed up for that date in legacy. `position_slots`
 * (the day's offered positions) are never touched here at all.
 *
 * Read-only against `legacy`: every call against that connection is a SELECT. This is what
 * makes it safe to point LEGACY_DB_* at a real production database and import prod -> whatever
 * DB_* targets (e.g. beta) without touching prod - pair it with a read-only DB user there too.
 *
 * Not migrated - the new schema has no destination for them:
 * - `bugs`, `file_storage` (bug reporting and per-week file uploads were scrapped in the rewrite)
 *
 * `shifts`/`rates` (evidencia hodín) aren't mentioned above because there's nothing left to skip -
 * both tables are gone from the destination schema entirely (see
 * 2026_09_12_130000_drop_shifts_and_rates_tables.php).
 */
class ImportLegacyData extends Command
{
    protected $signature = 'app:import-legacy-data
        {--team=1 : ID tímu, ktorý dostane reálne dáta}
        {--force : Preskočiť potvrdzovaciu otázku}';

    protected $description = 'Nahradí demo dáta daného tímu reálnymi dátami z legacy databázy (LEGACY_DB_* v .env)';

    /**
     * Fixed test accounts, exempt from clearSeededData()'s wipe (see there) so they survive
     * every re-run instead of being treated as demo data.
     */
    private const TEST_USERS = [
        ['email' => 'brigadnik@test.sk', 'name' => 'Test', 'lastname' => 'Brigádnik', 'role' => RoleEnum::Employee],
        ['email' => 'admin@test.sk', 'name' => 'Test', 'lastname' => 'Admin', 'role' => RoleEnum::HeadManager],
    ];

    private const TEST_PASSWORD = 'test1234';

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
            "Toto nahradí zamestnancov a absencie tímu '{$team->name}' reálnymi dátami z legacy databázy. ".
            'Zapísané zmeny sa zlúčia a rozpis pozícií zostane zachovaný. Pokračovať?'
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
                $this->ensureTestUsers($team);
                $this->importAssignments($legacy, $userMap);
                $this->importAbsences($legacy, $userMap);
            });
        });

        $this->info('Import dokončený.');
        $this->line('Preskočené (nová schéma pre ne nemá tabuľku): bugs, file_storage.');
        $this->line('Testovacie účty: brigadnik@test.sk / admin@test.sk (heslo: '.self::TEST_PASSWORD.')');

        return self::SUCCESS;
    }

    /**
     * Wipes this team's media/week-locks/absences outright (scoped by the
     * setPermissionsTeamId() global scope, see BelongsToTeam), then drops every member who has
     * no counterpart in the legacy data - unless they also belong to another team, in which
     * case only this team's membership goes.
     *
     * Assignments and position_slots are deliberately NOT cleared here - importAssignments()
     * diff-merges them instead, so a position placement already made in the rozpis builder
     * survives a re-run.
     */
    private function clearSeededData(Team $team, Connection $legacy): void
    {
        Media::query()->delete();
        WeekLock::query()->delete();
        Absence::query()->delete();

        $keepEmails = $legacy->table('users')->pluck('email')
            ->merge(array_column(self::TEST_USERS, 'email'));

        $demoUsers = $team->users()->whereNotIn('users.email', $keepEmails)->get();

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

    /** Creates or refreshes the fixed test accounts (see TEST_USERS) for this team. */
    private function ensureTestUsers(Team $team): void
    {
        foreach (self::TEST_USERS as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'lastname' => $data['lastname'],
                    'password' => Hash::make(self::TEST_PASSWORD),
                    'current_team_id' => $team->id,
                    'is_active' => true,
                    'email_verified_at' => now(),
                ]
            );

            $user->teams()->syncWithoutDetaching([$team->id => ['approved_at' => now()]]);
            $user->syncRoles([$data['role']->value]);
        }
    }

    /**
     * user_days -> assignments, diff-merged rather than wiped and reinserted. `days` is only
     * ever consulted here to resolve `id_day` to an actual date - resolved in PHP rather than a
     * SQL join, since `legacy` may be a genuinely separate database server that a cross-table
     * join can't reach.
     *
     * Legacy owns only the signup itself (who, which date, the free-text `popis` note) - never
     * a position. So on a row that already exists, only `note` is refreshed; `position_id`,
     * `position_slot_id`, `start_time` and `end_time` (written onto this same row by
     * RozpisDay::place() in the builder) are left exactly as they were. A signup missing from
     * this legacy pull gets deleted, since nobody keeps a position they are no longer signed up
     * for; a new signup is inserted bare, same as before ("admin fills in the position later").
     *
     * @param  array<int, int>  $userMap
     */
    private function importAssignments(Connection $legacy, array $userMap): void
    {
        $teamId = getPermissionsTeamId();

        // Normalised to a bare Y-m-d: the diff below matches these against
        // $assignment->date->toDateString(), so both sides need the same shape regardless of
        // whether legacy's `days.date` column carries a time component.
        $dayDates = $legacy->table('days')->pluck('date', 'id')
            ->map(fn (string $date): string => Carbon::parse($date)->toDateString());

        $rows = $legacy->table('user_days')->get(['id_user', 'id_day', 'popis']);

        // Keyed "userId|date" - legacy user_days has no unique (user, day) constraint, so a
        // duplicate signup silently overwrites the earlier one instead of failing the import.
        $incoming = [];

        foreach ($rows as $row) {
            $userId = $userMap[$row->id_user] ?? null;
            $date = $dayDates[$row->id_day] ?? null;

            if (! $userId || ! $date) {
                continue;
            }

            $incoming[$userId.'|'.$date] = $row->popis !== null ? mb_substr(trim($row->popis), 0, 255) : null;
        }

        Assignment::query()
            ->get(['id', 'user_id', 'date'])
            ->reject(fn (Assignment $assignment): bool => array_key_exists(
                $assignment->user_id.'|'.$assignment->date->toDateString(),
                $incoming
            ))
            ->each(fn (Assignment $assignment) => $assignment->delete());

        foreach ($incoming as $key => $note) {
            [$userId, $date] = explode('|', $key, 2);

            $assignment = Assignment::firstOrNew([
                'team_id' => $teamId,
                'user_id' => $userId,
                'date' => $date,
            ]);

            $assignment->note = $note;

            if (! $assignment->exists) {
                $assignment->created_by = null;
            }

            $assignment->save();
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
