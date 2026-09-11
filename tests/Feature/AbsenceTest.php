<?php

namespace Tests\Feature;

use App\Enums\AbsenceStatus;
use App\Enums\Role;
use App\Models\Absence;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbsenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_employee_can_report_an_absence(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $from = CarbonImmutable::now()->addWeek();

        $this->actingAs($user)
            ->post(route('absences.store'), [
                'date_from' => $from->toDateString(),
                'date_to' => $from->addDays(2)->toDateString(),
                'reason' => 'Skúškové',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('absences', [
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date_from' => $from->toDateString(),
            'reason' => 'Skúškové',
        ]);
    }

    /** The deadline is per-team configurable, not a hardcoded number. */
    public function test_an_absence_reported_past_a_custom_team_deadline_is_rejected(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $team->settings->update(['absence_deadline_days' => 5]);

        $in3Days = CarbonImmutable::now()->addDays(3)->toDateString();

        $this->actingAs($user)
            ->post(route('absences.store'), [
                'date_from' => $in3Days,
                'date_to' => $in3Days,
                'reason' => 'Dovolenka',
            ])
            ->assertSessionHasErrors('date_from');

        $this->assertDatabaseCount('absences', 0);

        $in6Days = CarbonImmutable::now()->addDays(6)->toDateString();

        $this->actingAs($user)
            ->post(route('absences.store'), [
                'date_from' => $in6Days,
                'date_to' => $in6Days,
                'reason' => 'Dovolenka',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('absences', 1);
    }

    /**
     * The deadline is the business rule worth testing: `absence_deadline_days` defaults to
     * 2, so tomorrow is too late to report.
     */
    public function test_an_absence_reported_past_the_deadline_is_rejected(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $tomorrow = CarbonImmutable::now()->addDay()->toDateString();

        $this->actingAs($user)
            ->post(route('absences.store'), [
                'date_from' => $tomorrow,
                'date_to' => $tomorrow,
                'reason' => 'Dovolenka',
            ])
            ->assertSessionHasErrors('date_from');

        $this->assertDatabaseCount('absences', 0);
    }

    public function test_an_employee_can_delete_their_own_absence(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $absence = Absence::factory()->create(['team_id' => $team->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('absences.destroy', $absence))
            ->assertRedirect();

        $this->assertModelMissing($absence);
    }

    /**
     * Past the ~15-minute just-made-a-mistake grace period, a still-active absence can no
     * longer be deleted outright - it must be cancelled (ended) first.
     */
    public function test_an_active_absence_past_the_creation_grace_period_cannot_be_deleted_directly(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $team->settings->update(['stale_absence_deletion_days' => 0]);

        $absence = Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date_from' => CarbonImmutable::now()->addWeek()->toDateString(),
            'date_to' => CarbonImmutable::now()->addWeek()->addDay()->toDateString(),
        ]);
        $this->backdateCreation($absence);

        $this->actingAs($user)
            ->delete(route('absences.destroy', $absence))
            ->assertForbidden();
        $this->assertModelExists($absence);

        $this->actingAs($user)
            ->patch(route('absences.end', $absence))
            ->assertRedirect();

        $this->actingAs($user)
            ->delete(route('absences.destroy', $absence))
            ->assertRedirect();
        $this->assertModelMissing($absence);
    }

    /**
     * The owner's mandatory retention period for an ended absence is per-team configurable
     * (`team_settings.stale_absence_deletion_days`), not a hardcoded number: not deletable
     * until it's been inactive for at least that many days.
     */
    public function test_stale_absence_deletion_respects_team_setting(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);

        $team->settings->update(['stale_absence_deletion_days' => 5]);

        $pastRetention = Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date_from' => CarbonImmutable::now()->subDays(10)->toDateString(),
            'date_to' => CarbonImmutable::now()->subDays(6)->toDateString(),
        ]);
        $this->backdateCreation($pastRetention);

        $stillWithinRetention = Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date_from' => CarbonImmutable::now()->subDays(8)->toDateString(),
            'date_to' => CarbonImmutable::now()->subDays(4)->toDateString(),
        ]);
        $this->backdateCreation($stillWithinRetention);

        $this->actingAs($user)
            ->delete(route('absences.destroy', $stillWithinRetention))
            ->assertForbidden();
        $this->assertModelExists($stillWithinRetention);

        $this->actingAs($user)
            ->delete(route('absences.destroy', $pastRetention))
            ->assertRedirect();
        $this->assertModelMissing($pastRetention);
    }

    /**
     * A future-dated absence cancelled today is inactive the moment it's cancelled, not on its
     * original (still future) date_to - so the retention period must be measured from the
     * cancellation. If it were wrongly measured from the still-far-future date_to, the diff
     * would be ~30 days and this absence would incorrectly already clear a 5-day retention.
     */
    public function test_cancelled_future_absence_respects_stale_deletion_window(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);

        $team->settings->update(['stale_absence_deletion_days' => 5]);

        $absence = Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date_from' => CarbonImmutable::now()->addMonth()->toDateString(),
            'date_to' => CarbonImmutable::now()->addMonth()->addDays(2)->toDateString(),
        ]);

        $absence->timestamps = false;
        $absence->forceFill([
            'status' => AbsenceStatus::Cancelled,
            'created_at' => CarbonImmutable::now()->subDays(2),
            'updated_at' => CarbonImmutable::now()->subDays(2),
        ])->save();

        $this->actingAs($user)
            ->delete(route('absences.destroy', $absence))
            ->assertForbidden();

        $this->assertModelExists($absence);
    }

    /** 0 means no waiting - an inactive absence is deletable as soon as it goes inactive. */
    public function test_zero_stale_absence_deletion_days_means_no_limit(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);

        $team->settings->update(['stale_absence_deletion_days' => 0]);

        $longEnded = Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date_from' => CarbonImmutable::now()->subDays(400)->toDateString(),
            'date_to' => CarbonImmutable::now()->subDays(390)->toDateString(),
        ]);

        $this->actingAs($user)
            ->delete(route('absences.destroy', $longEnded))
            ->assertRedirect();

        $this->assertModelMissing($longEnded);
    }

    /**
     * A manager/admin (viewer of the all-absences table) can delete any INACTIVE absence
     * regardless of the retention period, but never an active one directly - they have to
     * end it first, same requirement as the owner.
     */
    public function test_a_manager_can_delete_inactive_but_not_active_absences_of_others(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $employee = $this->member($team);

        $active = Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $employee->id,
            'date_from' => CarbonImmutable::now()->toDateString(),
            'date_to' => CarbonImmutable::now()->addWeek()->toDateString(),
        ]);
        $this->backdateCreation($active);

        $this->actingAs($manager)
            ->delete(route('absences.destroy', $active))
            ->assertForbidden();
        $this->assertModelExists($active);

        $inactive = Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $employee->id,
            'date_from' => CarbonImmutable::now()->subDays(5)->toDateString(),
            'date_to' => CarbonImmutable::now()->subDay()->toDateString(),
        ]);
        $this->backdateCreation($inactive);

        $this->actingAs($manager)
            ->delete(route('absences.destroy', $inactive))
            ->assertRedirect();
        $this->assertModelMissing($inactive);
    }

    /**
     * A manager/admin deleting someone ELSE's inactive absence bypasses the retention period
     * entirely - that's the point of the elevated 'absence.manage'/'absence.delete-inactive'
     * permission. The team's default retention (30 days) would normally block this.
     */
    public function test_a_manager_bypasses_the_retention_period_on_someone_elses_absence(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $employee = $this->member($team);

        $recentlyInactive = Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $employee->id,
            'date_from' => CarbonImmutable::now()->subDays(4)->toDateString(),
            'date_to' => CarbonImmutable::now()->subDays(2)->toDateString(),
        ]);
        $this->backdateCreation($recentlyInactive);

        $this->actingAs($manager)
            ->delete(route('absences.destroy', $recentlyInactive))
            ->assertRedirect();
        $this->assertModelMissing($recentlyInactive);
    }

    /**
     * The bug this test guards: an admin deleting their OWN cancelled absence must not get the
     * manager "can delete anyone's absence" branch confused with a retention-period bypass -
     * it's still their own absence, still subject to the same wait as anyone else's.
     */
    public function test_an_admin_cannot_bypass_the_retention_period_on_their_own_absence(): void
    {
        $team = $this->tenant();
        $admin = $this->member($team, Role::HeadManager);
        $team->settings->update(['stale_absence_deletion_days' => 5]);

        $absence = Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $admin->id,
            'date_from' => CarbonImmutable::now()->subDays(4)->toDateString(),
            'date_to' => CarbonImmutable::now()->subDays(2)->toDateString(),
        ]);
        $this->backdateCreation($absence);

        $this->actingAs($admin)
            ->delete(route('absences.destroy', $absence))
            ->assertForbidden();
        $this->assertModelExists($absence);
    }

    /** A manager/admin's reach over someone else's absence is delete-when-inactive only - never ending it for them. */
    public function test_a_manager_cannot_end_someone_elses_active_absence(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $employee = $this->member($team);

        $absence = Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $employee->id,
            'date_from' => CarbonImmutable::now()->toDateString(),
            'date_to' => CarbonImmutable::now()->addWeek()->toDateString(),
        ]);

        $this->actingAs($manager)
            ->patch(route('absences.end', $absence))
            ->assertForbidden();

        $this->assertSame(AbsenceStatus::Active, $absence->fresh()->status);
    }

    public function test_an_employee_cannot_delete_someone_elses_absence(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $colleague = $this->member($team);
        $absence = Absence::factory()->create(['team_id' => $team->id, 'user_id' => $colleague->id]);

        $this->actingAs($user)
            ->delete(route('absences.destroy', $absence))
            ->assertForbidden();

        $this->assertModelExists($absence);
    }

    /**
     * Ending a running absence sets status to cancelled and updates date_to.
     */
    public function test_ending_a_running_absence_shortens_it_to_today(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);

        $absence = Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date_from' => CarbonImmutable::now()->subDays(2)->toDateString(),
            'date_to' => CarbonImmutable::now()->addWeek()->toDateString(),
        ]);

        $this->actingAs($user)
            ->patch(route('absences.end', $absence))
            ->assertRedirect();

        $this->assertSame(AbsenceStatus::Cancelled, $absence->fresh()->status);
    }

    /** An absence deactivated in advance is marked as cancelled instead of being deleted. */
    public function test_ending_a_future_absence_marks_it_cancelled(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);

        $absence = Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date_from' => CarbonImmutable::now()->addWeek()->toDateString(),
            'date_to' => CarbonImmutable::now()->addWeeks(2)->toDateString(),
        ]);

        $this->actingAs($user)
            ->patch(route('absences.end', $absence))
            ->assertRedirect();

        $this->assertModelExists($absence);
        $this->assertSame(AbsenceStatus::Cancelled, $absence->fresh()->status);
    }

    /**
     * Factories stamp created_at as "now", which would otherwise always satisfy the
     * policy's short post-creation delete grace period regardless of the backdated
     * date_from/date_to a test sets up.
     */
    private function backdateCreation(Absence $absence): void
    {
        $absence->timestamps = false;
        $absence->forceFill(['created_at' => CarbonImmutable::now()->subDays(20)])->save();
    }
}
