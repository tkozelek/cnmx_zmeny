<?php

namespace Tests\Feature;

use App\Enums\AbsenceStatus;
use App\Enums\Role;
use App\Models\Absence;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportLegacyDataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config(['database.connections.legacy' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('legacy');

        $this->createLegacySchema();
    }

    public function test_the_legacy_cinema_moves_over_with_its_states_locks_and_history(): void
    {
        $team = $this->tenant();

        $this->legacyUser(1, 'admin@kino.sk', 3, '2024-05-01 10:00:00');
        $this->legacyUser(2, 'brigadnik@kino.sk', 2);
        $this->legacyUser(3, 'cakajuci@kino.sk', 1);
        $this->legacyUser(4, 'blokovany@kino.sk', 4);

        DB::connection('legacy')->table('weeks')->insert([
            ['id' => 1, 'date_from' => '2026-10-01', 'date_to' => '2026-10-07', 'locked' => true],
            ['id' => 2, 'date_from' => '2026-10-08', 'date_to' => '2026-10-14', 'locked' => false],
        ]);
        DB::connection('legacy')->table('days')->insert(['id' => 1, 'date' => '2026-10-01', 'id_week' => 1]);
        DB::connection('legacy')->table('user_days')->insert(['id_user' => 2, 'id_day' => 1, 'popis' => 'od 17:00']);
        DB::connection('legacy')->table('user_holidays')->insert([
            'id_user' => 2, 'popis' => 'dovolenka', 'date_from' => '2026-09-01', 'date_to' => '2026-09-03',
            'date_canceled' => null, 'created_at' => '2026-08-20 08:00:00', 'updated_at' => '2026-08-20 08:00:00',
        ]);

        $this->artisan('app:import-legacy-data', ['--team' => $team->id, '--force' => true])->assertSuccessful();

        $admin = User::where('email', 'admin@kino.sk')->sole();
        $this->assertTrue($admin->hasRole(Role::HeadManager->value));
        $this->assertSame('2024-05-01', $admin->created_at->toDateString());
        $this->assertSame('2024-05-01', $admin->updated_at->toDateString(), 'The import time must not become everyone\'s "last updated".');
        $this->assertNotNull(User::where('email', 'brigadnik@kino.sk')->sole()->email_verified_at);
        $this->assertFalse(User::where('email', 'blokovany@kino.sk')->sole()->is_active);
        $this->assertNull(User::where('email', 'cakajuci@kino.sk')->sole()->teams()->sole()->pivot->approved_at);

        $this->assertDatabaseHas('week_locks', ['team_id' => $team->id, 'week_start' => '2026-10-01']);
        $this->assertDatabaseCount('week_locks', 1);
        $this->assertSame('od 17:00', Assignment::sole()->note);
        $this->assertSame('2026-08-20', Absence::sole()->created_at->toDateString());
        $this->assertDatabaseMissing('users', ['email' => config('services.test_users.admin_email')]);
    }

    public function test_a_rerun_refuses_to_wipe_what_was_written_in_the_new_app(): void
    {
        $team = $this->tenant();
        $this->legacyUser(1, 'brigadnik@kino.sk', 2);

        $this->artisan('app:import-legacy-data', ['--team' => $team->id, '--force' => true])->assertSuccessful();

        $this->travel(10)->minutes();
        Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => User::where('email', 'brigadnik@kino.sk')->sole()->id,
            'date' => '2026-10-22',
        ]);

        $this->artisan('app:import-legacy-data', ['--team' => $team->id, '--force' => true])->assertFailed();
        $this->assertDatabaseCount('assignments', 1);

        $this->artisan('app:import-legacy-data', ['--team' => $team->id, '--force' => true, '--discard-new-data' => true])->assertSuccessful();
        $this->assertDatabaseCount('assignments', 0);
    }

    /**
     * Legacy stamped date_canceled on any "ukončiť", even mid-absence: those days were taken and
     * must stay; only an absence ended before it started is a real cancellation.
     */
    public function test_ended_absences_keep_the_days_already_taken_and_emails_are_normalised(): void
    {
        $team = $this->tenant();
        $this->legacyUser(1, '  Jana.Novakova@Kino.SK ', 2);

        DB::connection('legacy')->table('user_holidays')->insert([
            ['id_user' => 1, 'popis' => 'skúšky', 'date_from' => '2026-09-01', 'date_to' => '2026-09-10', 'date_canceled' => '2026-09-05 10:00:00', 'created_at' => null, 'updated_at' => null],
            ['id_user' => 1, 'popis' => 'výlet', 'date_from' => '2026-09-20', 'date_to' => '2026-09-22', 'date_canceled' => '2026-09-15 10:00:00', 'created_at' => null, 'updated_at' => null],
        ]);

        $this->artisan('app:import-legacy-data', ['--team' => $team->id, '--force' => true])->assertSuccessful();

        $this->assertDatabaseHas('users', ['email' => 'jana.novakova@kino.sk']);

        $endedEarly = Absence::where('date_from', '2026-09-01')->sole();
        $this->assertSame(AbsenceStatus::Active, $endedEarly->status);
        $this->assertSame('2026-09-04', $endedEarly->date_to->toDateString());

        $this->assertSame(AbsenceStatus::Cancelled, Absence::where('date_from', '2026-09-20')->sole()->status);
    }

    public function test_a_dry_run_checks_the_counts_and_changes_nothing(): void
    {
        $team = $this->tenant();
        $this->legacyUser(1, 'brigadnik@kino.sk', 2);

        $this->artisan('app:import-legacy-data', ['--team' => $team->id, '--dry-run' => true])->assertSuccessful();

        $this->assertDatabaseMissing('users', ['email' => 'brigadnik@kino.sk']);
    }

    /** A rollback-and-reimport must not silently delete someone who registered in the new app. */
    public function test_a_rerun_refuses_after_someone_joined_in_the_new_app(): void
    {
        $team = $this->tenant();
        $this->legacyUser(1, 'brigadnik@kino.sk', 2);

        $this->artisan('app:import-legacy-data', ['--team' => $team->id, '--force' => true])->assertSuccessful();

        $this->travel(10)->minutes();
        User::factory()->pendingIn($team)->create();

        $this->artisan('app:import-legacy-data', ['--team' => $team->id, '--force' => true])->assertFailed();
    }

    /** Legacy never withdrew a blocked or pending person's sign-ups; the new app would show them working. */
    public function test_future_sign_ups_of_blocked_and_pending_people_stay_behind(): void
    {
        $team = $this->tenant();
        $past = now()->subWeek()->toDateString();
        $future = now()->addWeek()->toDateString();

        $this->legacyUser(1, 'aktivny@kino.sk', 2);
        $this->legacyUser(2, 'blokovany@kino.sk', 4);
        $this->legacyUser(3, 'cakajuci@kino.sk', 1);
        DB::connection('legacy')->table('days')->insert([['id' => 1, 'date' => $past, 'id_week' => 1], ['id' => 2, 'date' => $future, 'id_week' => 2]]);
        DB::connection('legacy')->table('user_days')->insert([
            ['id_user' => 1, 'id_day' => 2, 'popis' => null],
            ['id_user' => 2, 'id_day' => 1, 'popis' => null],
            ['id_user' => 2, 'id_day' => 2, 'popis' => null],
            ['id_user' => 3, 'id_day' => 2, 'popis' => null],
        ]);

        $this->artisan('app:import-legacy-data', ['--team' => $team->id, '--force' => true])->assertSuccessful();

        $blocked = User::where('email', 'blokovany@kino.sk')->sole();
        $this->assertSame([$past], Assignment::where('user_id', $blocked->id)->get()->map(fn (Assignment $a): string => $a->date->toDateString())->all());
        $this->assertSame(0, Assignment::where('user_id', User::where('email', 'cakajuci@kino.sk')->sole()->id)->count());
        $this->assertSame(1, Assignment::where('user_id', User::where('email', 'aktivny@kino.sk')->sole()->id)->count());
    }

    /** Through the `hashed` cast a "$2b$" hash was hashed again - that person could never log in. */
    public function test_a_legacy_2b_hash_above_the_current_cost_logs_in_and_is_rehashed(): void
    {
        $team = $this->tenant();
        $this->legacyUser(1, 'brigadnik@kino.sk', 2);
        $legacyHash = '$2b$'.substr(password_hash('heslo1234', PASSWORD_BCRYPT, ['cost' => 10]), 4);
        DB::connection('legacy')->table('users')->where('id', 1)->update(['password' => $legacyHash]);

        $this->artisan('app:import-legacy-data', ['--team' => $team->id, '--force' => true])->assertSuccessful();

        $this->post(route('login.auth'), ['email' => 'brigadnik@kino.sk', 'password' => 'heslo1234'])
            ->assertRedirect(route('calendar.index'));

        $this->assertStringStartsWith('$2y$04$', User::where('email', 'brigadnik@kino.sk')->sole()->password, 'Re-hashed at the current cost on the first login.');
    }

    public function test_the_import_refuses_a_super_admin_email_and_a_lock_off_the_week_start(): void
    {
        $team = $this->tenant();
        $this->legacyUser(1, 'operator@kino.sk', 2);
        User::factory()->create(['email' => 'operator@kino.sk'])->forceFill(['is_super_admin' => true])->save();

        $this->artisan('app:import-legacy-data', ['--team' => $team->id, '--force' => true])
            ->expectsOutputToContain('super admina')
            ->assertFailed();

        User::where('email', 'operator@kino.sk')->update(['is_super_admin' => false]);
        // 2026-10-05 is a Monday; the team's weeks start on Thursday.
        DB::connection('legacy')->table('weeks')->insert(['id' => 1, 'date_from' => '2026-10-05', 'date_to' => '2026-10-11', 'locked' => true]);

        $this->artisan('app:import-legacy-data', ['--team' => $team->id, '--force' => true])
            ->expectsOutputToContain('2026-10-05')
            ->assertFailed();

        $this->assertDatabaseCount('week_locks', 0);

        DB::connection('legacy')->table('weeks')->delete();
        // An MD5 leftover: nobody could ever log in with it.
        DB::connection('legacy')->table('users')->where('id', 1)->update(['password' => md5('heslo1234')]);

        $this->artisan('app:import-legacy-data', ['--team' => $team->id, '--force' => true])
            ->expectsOutputToContain('nie je bcrypt')
            ->assertFailed();
    }

    private function legacyUser(int $id, string $email, int $role, ?string $createdAt = null): void
    {
        DB::connection('legacy')->table('users')->insert([
            'id' => $id,
            'name' => 'Meno',
            'lastname' => 'Priezvisko',
            'email' => $email,
            'password' => bcrypt('heslo1234'),
            'id_role' => $role,
            'last_login_at' => null,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
    }

    /** The subset of the legacy (master branch) schema the import reads. */
    private function createLegacySchema(): void
    {
        $schema = Schema::connection('legacy');

        $schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lastname');
            $table->string('email');
            $table->string('password');
            $table->unsignedInteger('id_role');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
        });
        $schema->create('weeks', function (Blueprint $table) {
            $table->id();
            $table->date('date_from');
            $table->date('date_to');
            $table->boolean('locked');
        });
        $schema->create('days', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedInteger('id_week');
        });
        $schema->create('user_days', function (Blueprint $table) {
            $table->id();
            $table->string('popis')->nullable();
            $table->unsignedInteger('id_user');
            $table->unsignedInteger('id_day');
        });
        $schema->create('user_holidays', function (Blueprint $table) {
            $table->id();
            $table->string('popis')->nullable();
            $table->dateTime('date_from');
            $table->dateTime('date_to');
            $table->dateTime('date_canceled')->nullable();
            $table->unsignedInteger('id_user');
            $table->timestamps();
        });
    }
}
