<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Team;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeekLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_can_lock_and_unlock_a_week(): void
    {
        $team = $this->tenant();
        $admin = $this->member($team, Role::HeadManager);
        $date = CarbonImmutable::now()->addDays(3)->toDateString();

        $this->actingAs($admin)->post(route('weeks.lock', ['date' => $date]))->assertRedirect();
        $this->assertDatabaseHas('week_locks', [
            'team_id' => $team->id,
            'week_start' => $this->weekStart($team, $date),
            'locked_by' => $admin->id,
        ]);

        $this->actingAs($admin)->delete(route('weeks.unlock', ['date' => $date]))->assertRedirect();
        $this->assertDatabaseCount('week_locks', 0);
    }

    public function test_an_employee_cannot_lock_a_week(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);

        $this->actingAs($employee)
            ->post(route('weeks.lock', ['date' => CarbonImmutable::now()->addDays(3)->toDateString()]))
            ->assertForbidden();

        $this->assertDatabaseCount('week_locks', 0);
    }

    /**
     * A week is keyed by its first day, so any date inside it must lock the same week -
     * that realignment is what stops a hand-edited URL creating a second, offset lock.
     */
    public function test_any_date_in_the_week_locks_the_same_week(): void
    {
        $team = $this->tenant();
        $admin = $this->member($team, Role::HeadManager);

        $weekStart = $this->weekStart($team, CarbonImmutable::now()->addDays(3)->toDateString());

        $this->actingAs($admin)->post(route('weeks.lock', ['date' => $weekStart]))->assertRedirect();
        $this->actingAs($admin)->post(route('weeks.lock', [
            'date' => CarbonImmutable::parse($weekStart)->addDays(4)->toDateString(),
        ]))->assertRedirect();

        $this->assertDatabaseCount('week_locks', 1);
    }

    private function weekStart(Team $team, string $date): string
    {
        return app(WeekService::class)->start($team, CarbonImmutable::parse($date))->toDateString();
    }
}
