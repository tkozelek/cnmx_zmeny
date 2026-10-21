<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Absence;
use App\Models\Assignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PrunePersonalDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_applicants_and_quiet_members_are_deleted_everyone_else_stays(): void
    {
        $team = $this->tenant();
        $longAgo = now()->subMonths(13);

        $oldApplicant = User::factory()->pendingIn($team)->create(['created_at' => now()->subMonths(7)]);
        $freshApplicant = User::factory()->pendingIn($team)->create(['created_at' => now()->subMonths(2)]);

        $quietMember = User::factory()->memberOf($team, Role::Employee->value)->create(['created_at' => $longAgo, 'last_login_at' => $longAgo]);
        Absence::factory()->create(['team_id' => $team->id, 'user_id' => $quietMember->id]);

        $activeMember = User::factory()->memberOf($team, Role::Employee->value)->create(['created_at' => $longAgo, 'last_login_at' => $longAgo]);
        Assignment::factory()->create(['team_id' => $team->id, 'user_id' => $activeMember->id, 'date' => now()->subMonth()->toDateString()]);

        $quietManager = User::factory()->memberOf($team, Role::HeadManager->value)->create(['created_at' => $longAgo, 'last_login_at' => $longAgo]);

        $this->artisan('app:prune-personal-data')->assertSuccessful();

        $this->assertModelMissing($oldApplicant);
        $this->assertModelMissing($quietMember);
        $this->assertDatabaseMissing('absences', ['user_id' => $quietMember->id]);
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $quietMember->id]);

        $this->assertModelExists($freshApplicant);
        $this->assertModelExists($activeMember);
        $this->assertModelExists($quietManager);
    }

    public function test_old_log_files_activity_and_expired_cache_entries_are_removed(): void
    {
        $oldActivity = DB::table('activity_log')->insertGetId(['description' => 'old', 'created_at' => now()->subMonths(13), 'updated_at' => now()->subMonths(13)]);
        $freshActivity = DB::table('activity_log')->insertGetId(['description' => 'fresh', 'created_at' => now()->subMonths(11), 'updated_at' => now()->subMonths(11)]);

        $oldLog = storage_path('logs/prune-test-old.log');
        $freshLog = storage_path('logs/prune-test-fresh.log');
        $expired = storage_path('framework/cache/data/zz/prune-test-expired');
        $valid = storage_path('framework/cache/data/zz/prune-test-valid');

        File::ensureDirectoryExists(dirname($expired));
        File::put($oldLog, 'old');
        touch($oldLog, now()->subDays(30)->getTimestamp());
        File::put($freshLog, 'fresh');
        File::put($expired, now()->subMinute()->getTimestamp().serialize('hash-of-an-ip'));
        File::put($valid, now()->addHour()->getTimestamp().serialize('hash-of-an-ip'));

        $this->artisan('app:prune-personal-data')->assertSuccessful();

        $this->assertFileDoesNotExist($oldLog);
        $this->assertFileDoesNotExist($expired);
        $this->assertFileExists($freshLog);
        $this->assertFileExists($valid);
        $this->assertDatabaseMissing('activity_log', ['id' => $oldActivity]);
        $this->assertDatabaseHas('activity_log', ['id' => $freshActivity]);

        File::delete([$freshLog, $valid]);
    }
}
