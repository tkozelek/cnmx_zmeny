<?php

namespace App\Console\Commands;

use App\Enums\AbsenceStatus;
use App\Enums\Role as RoleEnum;
use App\Models\Absence;
use App\Models\Assignment;
use App\Models\Team;
use App\Models\User;
use App\Models\WeekLock;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Import from the legacy production schema (see the `legacy` connection in
 * config/database.php - LEGACY_DB_* in .env) into the rewritten schema. Users and absences are
 * replaced from legacy, assignments are diff-merged, and locked legacy weeks become week locks.
 *
 * Re-runnable for rehearsals, but NOT safe to cron: a run wipes the team's absences and locks and
 * drops sign-ups legacy doesn't have. After each run it records when it finished, and refuses to
 * run again once the team has data written in the new app since then (see newDataSinceLastRun()).
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
        {--team= : ID tímu, ktorý dostane reálne dáta (povinné)}
        {--dry-run : Celý import prebehne a skontroluje počty, potom sa všetko vráti späť}
        {--with-test-users : Vytvoriť aj testovacie účty (TEST_USER_* v .env) - nie na produkcii}
        {--discard-new-data : Prepísať aj dáta zapísané v novej aplikácii od posledného importu}
        {--force : Preskočiť potvrdzovaciu otázku}';

    protected $description = 'Nahradí demo dáta daného tímu reálnymi dátami z legacy databázy (LEGACY_DB_* v .env)';

    /**
     * Fixed test accounts, exempt from clearSeededData()'s wipe (see there) so they survive
     * every re-run instead of being treated as demo data. Email/password come from config
     * (services.test_users, backed by TEST_USER_* in .env) rather than being hardcoded, since
     * this repo is public.
     *
     * @return list<array{email: string, name: string, lastname: string, role: RoleEnum}>
     */
    private function testUsers(): array
    {
        return [
            ['email' => config('services.test_users.brigadnik_email'), 'name' => 'Test', 'lastname' => 'Brigádnik', 'role' => RoleEnum::Employee],
            ['email' => config('services.test_users.admin_email'), 'name' => 'Test', 'lastname' => 'Admin', 'role' => RoleEnum::HeadManager],
        ];
    }

    public function handle(): int
    {
        // Only a real server needs real credentials; the tests point `legacy` at in-memory SQLite.
        if (config('database.connections.legacy.driver') !== 'sqlite' && blank(config('database.connections.legacy.username'))) {
            $this->error('Chýba LEGACY_DB_USERNAME - zadaj používateľa, ktorý má na legacy databáze len SELECT.');

            return self::FAILURE;
        }

        $legacy = DB::connection('legacy');

        try {
            $legacy->getPdo();
        } catch (Throwable $e) {
            $this->error("Nepodarilo sa pripojiť na legacy databázu: {$e->getMessage()}");

            return self::FAILURE;
        }

        // Enforced by MySQL itself, whatever the user's grants: the legacy DB is production and
        // this command must never be able to write to it, not even through a bug here.
        if ($legacy->getDriverName() === 'mysql') {
            $legacy->statement('SET SESSION TRANSACTION READ ONLY');
        }

        if (! Schema::connection('legacy')->hasTable('users')) {
            $this->error('Legacy databáza neobsahuje tabuľku "users" - skontroluj LEGACY_DB_* v .env.');

            return self::FAILURE;
        }

        if (blank($this->option('team'))) {
            $this->error('Zadaj --team=<id> - import nikdy nehádže dáta do predvoleného kina.');

            return self::FAILURE;
        }

        $team = Team::find((int) $this->option('team'));

        if (! $team) {
            $this->error("Tím s ID {$this->option('team')} neexistuje.");

            return self::FAILURE;
        }

        if ($this->option('with-test-users') && blank(config('services.test_users.password'))) {
            $this->error('--with-test-users vyžaduje TEST_USER_PASSWORD v .env.');

            return self::FAILURE;
        }

        $duplicates = $legacy->table('users')->pluck('email')
            ->map($this->normalizeEmail(...))
            ->duplicates();

        if ($duplicates->isNotEmpty()) {
            $this->error('Legacy e-maily sa po orezaní medzier a malých písmenách zhodujú (spojili by sa dvaja ľudia): '.$duplicates->implode(', '));

            return self::FAILURE;
        }

        if (! $this->option('discard-new-data') && ($since = $this->newDataSinceLastRun($team))) {
            $this->error("Tím '{$team->name}' má od posledného importu ({$since}) nové alebo upravené zápisy, absencie, zámky či členov - ".
                'import by ich zmazal alebo prepísal. Ak to naozaj chceš, spusti ho s --discard-new-data.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->option('dry-run') && ! $this->confirm(
            "Toto nahradí brigádnikov a absencie tímu '{$team->name}' reálnymi dátami z legacy databázy. ".
            'Zapísané zmeny sa zlúčia. Pokračovať?'
        )) {
            return self::SUCCESS;
        }

        DB::beginTransaction();

        try {
            setPermissionsTeamId($team->id);

            // A few thousand historical rows landing in the activity log would bury the real
            // edits the rozpis history panel exists to show - same reasoning as SampleAssignmentsSeeder.
            activity()->withoutLogging(function () use ($team, $legacy): void {
                $this->clearSeededData($team, $legacy);
                $userMap = $this->importUsers($team, $legacy);

                if ($this->option('with-test-users')) {
                    $this->ensureTestUsers($team);
                }

                // No model events: a re-run over a published week would otherwise queue "rozpis
                // changed" mails, and every row would write an audit line.
                Assignment::withoutEvents(fn () => $this->importAssignments($legacy, $userMap));
                $this->importAbsences($legacy, $userMap);
                $this->importWeekLocks($legacy);
            });

            $mismatches = $this->reconcile($team, $legacy);
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }

        if ($mismatches > 0) {
            DB::rollBack();
            $this->error("Počty nesedia ({$mismatches}) - import bol vrátený späť, nič sa nezmenilo.");

            return self::FAILURE;
        }

        if ($this->option('dry-run')) {
            DB::rollBack();
            $this->info('Skúšobný beh (--dry-run): počty sedia, všetko bolo vrátené späť.');

            return self::SUCCESS;
        }

        DB::commit();

        Storage::disk('local')->put($this->markerPath($team), now()->toDateTimeString());

        Log::info('Import z legacy databázy dokončený.', ['team_id' => $team->id]);

        $this->info('Import dokončený.');
        $this->line('Preskočené (nová schéma pre ne nemá tabuľku): bugs, file_storage, shifts, rates.');

        if ($this->option('with-test-users')) {
            $this->line('Testovacie účty: '.implode(' / ', array_column($this->testUsers(), 'email')));
        }

        return self::SUCCESS;
    }

    private function markerPath(Team $team): string
    {
        return "legacy-import/team-{$team->id}.txt";
    }

    /**
     * When the previous run finished, if anything in this team has been written or changed since -
     * a sign-up, an absence, a lock, a new or edited member - which the next run would wipe or
     * overwrite. The marker lives on the `local` disk, so `storage/` must be shared between releases.
     */
    private function newDataSinceLastRun(Team $team): ?string
    {
        $finishedAt = Storage::disk('local')->get($this->markerPath($team));

        if ($finishedAt === null) {
            return null;
        }

        $touchedSince = fn ($query) => $query->where(fn ($query) => $query
            ->where('created_at', '>', $finishedAt)
            ->orWhere('updated_at', '>', $finishedAt));

        $hasNewData = Assignment::query()->withoutGlobalScope('team')->where('team_id', $team->id)->where($touchedSince)->exists()
            || Absence::query()->withoutGlobalScope('team')->where('team_id', $team->id)->where($touchedSince)->exists()
            || WeekLock::query()->withoutGlobalScope('team')->where('team_id', $team->id)->where('created_at', '>', $finishedAt)->exists()
            // A registration, an approval or a profile edit in the new app - clearSeededData()
            // would delete the newcomer, importUsers() would overwrite the edit.
            || DB::table('team_user')->where('team_id', $team->id)->where($touchedSince)->exists()
            || $team->users()->where(fn ($query) => $query
                ->where('users.created_at', '>', $finishedAt)
                ->orWhere('users.updated_at', '>', $finishedAt))->exists();

        return $hasNewData ? $finishedAt : null;
    }

    /**
     * Wipes this team's week-locks/absences outright (scoped by the
     * setPermissionsTeamId() global scope, see BelongsToTeam), then drops every member who has
     * no counterpart in the legacy data - unless they also belong to another team, in which
     * case only this team's membership goes.
     *
     * Assignments are deliberately NOT cleared here - importAssignments() diff-merges them
     * instead.
     */
    private function clearSeededData(Team $team, Connection $legacy): void
    {
        WeekLock::query()->delete();
        Absence::query()->delete();

        $keepEmails = $legacy->table('users')->pluck('email')
            ->map($this->normalizeEmail(...))
            ->merge(array_column($this->testUsers(), 'email'));

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
     * Legacy id_role: 1 neoverený, 2 brigádnik, 3 administrátor, 4 zablokovaný. Not-yet-approved
     * (1) keeps a pending membership and blocked (4) stays inactive - both remain employees, as
     * that is where those states live now (see App\Enums\Role).
     *
     * Every imported address counts as verified: in legacy, the manager's approval was the check.
     *
     * @return array<int, int> legacy user id => new user id
     */
    private function importUsers(Team $team, Connection $legacy): array
    {
        $map = [];

        foreach ($legacy->table('users')->get() as $legacyUser) {
            $legacyRole = (int) $legacyUser->id_role;

            $user = User::updateOrCreate(
                ['email' => $this->normalizeEmail($legacyUser->email)],
                [
                    'name' => $legacyUser->name,
                    'lastname' => $legacyUser->lastname,
                    'password' => $legacyUser->password,
                    'current_team_id' => $team->id,
                    'is_active' => $legacyRole !== 4,
                    'email_verified_at' => now(),
                    'last_login_at' => $legacyUser->last_login_at,
                ]
            );

            if ($legacyUser->created_at !== null) {
                $user->created_at = $legacyUser->created_at;
                $user->save();
            }

            $user->teams()->syncWithoutDetaching([
                $team->id => ['approved_at' => $legacyRole === 1 ? null : ($legacyUser->created_at ?? now())],
            ]);

            $role = $legacyRole === 3 ? RoleEnum::HeadManager : RoleEnum::Employee;
            $user->syncRoles([$role->value]);

            $map[$legacyUser->id] = $user->id;
        }

        return $map;
    }

    /** Creates or refreshes the fixed test accounts (see testUsers()) for this team. */
    private function ensureTestUsers(Team $team): void
    {
        foreach ($this->testUsers() as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'lastname' => $data['lastname'],
                    'password' => Hash::make(config('services.test_users.password')),
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
     * for; a new signup is inserted bare.
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
     * user_holidays -> absences. A legacy "ukončiť" (date_canceled set) is mapped the way
     * AbsenceService::end() works now - see endedAbsence().
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

            [$dateTo, $status] = $this->endedAbsence(
                Carbon::parse($row->date_from)->startOfDay(),
                Carbon::parse($row->date_to)->startOfDay(),
                $row->date_canceled !== null ? Carbon::parse($row->date_canceled)->startOfDay() : null,
            );

            $insert[] = [
                'team_id' => getPermissionsTeamId(),
                'user_id' => $userId,
                'date_from' => Carbon::parse($row->date_from)->toDateString(),
                'date_to' => $dateTo->toDateString(),
                'day_of_week' => null,
                'reason' => $row->popis !== null ? mb_substr($row->popis, 0, 500) : null,
                'status' => $status->value,
                // The original time, not "now": AbsencePolicy lets a fresh absence be deleted outright.
                'created_at' => $row->created_at ?? $now,
                'updated_at' => $row->updated_at ?? $now,
            ];
        }

        foreach (array_chunk($insert, 500) as $chunk) {
            DB::table('absences')->insert($chunk);
        }
    }

    /**
     * Legacy `weeks.locked` -> week_locks. Legacy weeks start on Thursday, which is also the
     * default TeamSetting::DEFAULT_WEEK_START_DAY, so `date_from` is the week_start as-is.
     */
    private function importWeekLocks(Connection $legacy): void
    {
        $legacy->table('weeks')->where('locked', true)->pluck('date_from')
            ->each(fn (string $dateFrom) => WeekLock::firstOrCreate([
                'team_id' => getPermissionsTeamId(),
                'week_start' => Carbon::parse($dateFrom)->toDateString(),
            ]));
    }

    /**
     * Legacy stamped `date_canceled` on any "ukončiť", even on an absence already running. The
     * new app cancels only one that hasn't started; a running one stays active, cut short
     * after the day before it was ended - so the days actually taken still count.
     *
     * @return array{0: Carbon, 1: AbsenceStatus}
     */
    private function endedAbsence(Carbon $from, Carbon $to, ?Carbon $canceled): array
    {
        if ($canceled === null) {
            return [$to, AbsenceStatus::Active];
        }

        if ($canceled->lt($from)) {
            return [$to, AbsenceStatus::Cancelled];
        }

        return [$from->max($to->min($canceled->copy()->subDay())), AbsenceStatus::Active];
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Legacy counts vs what landed in the team, computed straight from the legacy tables rather
     * than from the import's own bookkeeping, so a mapping bug can't agree with itself.
     *
     * @return int number of mismatching rows
     */
    private function reconcile(Team $team, Connection $legacy): int
    {
        $legacyUsers = $legacy->table('users')->get(['id', 'email', 'id_role']);
        $emails = $legacyUsers->pluck('email')->map($this->normalizeEmail(...));
        $imported = $team->users()->whereIn('users.email', $emails)->get();
        $importedIds = $imported->modelKeys();

        $dayDates = $legacy->table('days')->pluck('date', 'id')
            ->map(fn (string $date): string => Carbon::parse($date)->toDateString());
        $legacyUserIds = $legacyUsers->pluck('id')->all();

        $holidays = $legacy->table('user_holidays')->whereIn('id_user', $legacyUserIds)->get(['date_from', 'date_canceled']);

        $rows = [
            ['Používatelia', $legacyUsers->count(), $imported->count()],
            ['  s overeným e-mailom', $legacyUsers->count(), $imported->whereNotNull('email_verified_at')->count()],
            ['  čakajúci na schválenie (rola 1)', $legacyUsers->where('id_role', 1)->count(), $imported->whereNull('pivot.approved_at')->count()],
            ['  zablokovaní (rola 4)', $legacyUsers->where('id_role', 4)->count(), $imported->where('is_active', false)->count()],
            ['  hlavní manažéri (rola 3)', $legacyUsers->where('id_role', 3)->count(), $imported->filter(fn (User $user): bool => $user->hasRole(RoleEnum::HeadManager->value))->count()],
            ['Zápisy (bez duplicít)', $legacy->table('user_days')->whereIn('id_user', $legacyUserIds)->get(['id_user', 'id_day'])
                ->filter(fn ($row): bool => isset($dayDates[$row->id_day]))
                ->map(fn ($row): string => $row->id_user.'|'.$dayDates[$row->id_day])
                ->unique()->count(),
                Assignment::query()->whereIn('user_id', $importedIds)->count()],
            ['Absencie', $holidays->count(), Absence::query()->whereIn('user_id', $importedIds)->count()],
            ['  zrušené pred začiatkom', $holidays->filter(fn ($row): bool => $row->date_canceled !== null
                && Carbon::parse($row->date_canceled)->startOfDay()->lt(Carbon::parse($row->date_from)->startOfDay()))->count(),
                Absence::query()->whereIn('user_id', $importedIds)->where('status', AbsenceStatus::Cancelled->value)->count()],
            ['Zamknuté týždne', $legacy->table('weeks')->where('locked', true)->distinct()->count('date_from'), WeekLock::query()->count()],
        ];

        $this->table(['', 'legacy', 'nová DB', ''], array_map(
            fn (array $row): array => [...$row, $row[1] === $row[2] ? 'OK' : 'NESEDÍ'],
            $rows,
        ));

        return count(array_filter($rows, fn (array $row): bool => $row[1] !== $row[2]));
    }
}
