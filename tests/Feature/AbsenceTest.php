<?php

namespace Tests\Feature;

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

    /**
     * The deadline is the business rule worth testing: `absence_deadline_hours` defaults to
     * 48, so tomorrow is too late to report.
     */
    public function test_an_absence_reported_past_the_deadline_is_rejected(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $tomorrow = CarbonImmutable::now()->addDay()->toDateString();

        $this->actingAs($user)
            ->post(route('absences.store'), ['date_from' => $tomorrow, 'date_to' => $tomorrow])
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
     * Ending a running absence shortens it to today rather than deleting the history —
     * there is no `date_canceled` column any more.
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

        $this->assertSame(
            CarbonImmutable::now()->toDateString(),
            $absence->fresh()->date_to->toDateString(),
        );
    }

    /** An absence that has not started yet has no history to keep, so it is removed. */
    public function test_ending_a_future_absence_deletes_it(): void
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

        $this->assertModelMissing($absence);
    }
}
