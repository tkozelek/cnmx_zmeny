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
