<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Enums\TeamFeature;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Pennant\Feature;
use Tests\TestCase;

class RozpisFeatureFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_cinema_without_the_module_has_no_rozpis(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::HeadManager);
        $weekStart = app(WeekService::class)->start($team, CarbonImmutable::now());
        $team->weekLocks()->create(['week_start' => $weekStart->toDateString()]);

        $this->actingAs($manager)->get(route('positions.index'))->assertNotFound();
        $this->actingAs($manager)->get(route('rozpis.show', ['date' => $weekStart->toDateString()]))->assertNotFound();
        $this->actingAs($manager)->get(route('help'))->assertOk()->assertDontSee('Pozície');
        $this->actingAs($manager)
            ->get(route('calendar.show', ['date' => $weekStart->toDateString()]))
            ->assertOk()
            ->assertDontSee(route('rozpis.show', ['date' => $weekStart->toDateString()]));
    }

    public function test_switching_the_module_on_opens_the_rozpis(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::HeadManager);
        $weekStart = app(WeekService::class)->start($team, CarbonImmutable::now());
        $team->weekLocks()->create(['week_start' => $weekStart->toDateString()]);

        Feature::for($team)->activate(TeamFeature::Rozpis);

        $this->actingAs($manager)->get(route('positions.index'))->assertOk();
        $this->actingAs($manager)->get(route('rozpis.show', ['date' => $weekStart->toDateString()]))->assertOk();
        $this->actingAs($manager)
            ->get(route('calendar.show', ['date' => $weekStart->toDateString()]))
            ->assertSee(route('rozpis.show', ['date' => $weekStart->toDateString()]));
    }

    public function test_settings_save_without_fairness_fields_when_the_module_is_off(): void
    {
        $team = $this->tenant();

        $this->actingAs($this->member($team, Role::HeadManager))
            ->put(route('team.settings.update'), [
                'name' => $team->name,
                'week_start_day' => 3,
                'week_lookahead' => 4,
                'absence_deadline_days' => 2,
                'stale_absence_deletion_days' => 7,
                'fairness_window_weeks' => 40,
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('message');

        $this->assertDatabaseHas('team_settings', [
            'team_id' => $team->id,
            'week_lookahead' => 4,
            'fairness_window_weeks' => 12,
        ]);
    }
}
