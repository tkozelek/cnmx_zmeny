<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Assignment;
use App\Models\Media;
use App\Models\Position;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_employee_sees_the_week(): void
    {
        $team = $this->tenant();

        $response = $this->actingAs($this->member($team))->get(route('calendar.index'));

        $response->assertOk();

        foreach (['Pondelok', 'Utorok', 'Streda', 'Štvrtok', 'Piatok', 'Sobota', 'Nedeľa'] as $dayName) {
            $response->assertSee($dayName);
        }

        $response->assertSee('Zapísať');
        $response->assertSee('Extra info');
    }

    public function test_an_admin_sees_the_week_controls(): void
    {
        $team = $this->tenant();

        $this->actingAs($this->member($team, Role::HeadManager))
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee('Zamknúť týždeň')
            ->assertSee('Excel');
    }

    public function test_a_locked_week_offers_no_signup(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $weekStart = app(WeekService::class)->start($team, CarbonImmutable::now());

        $team->weekLocks()->create([
            'week_start' => $weekStart->toDateString(),
        ]);

        $this->actingAs($user)
            ->get(route('calendar.show', ['date' => $weekStart->toDateString()]))
            ->assertOk()
            ->assertSee('Zamknutý')
            ->assertDontSee('Zapísať');
    }

    public function test_default_landing_page_opens_closest_unlocked_week(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $weeks = app(WeekService::class);
        $currentWeek = $weeks->start($team, CarbonImmutable::now());

        $team->weekLocks()->create([
            'week_start' => $currentWeek->toDateString(),
        ]);

        $nextWeek = $currentWeek->addWeek();

        $this->actingAs($user)
            ->get(route('calendar.index'))
            ->assertOk()
            ->assertSee($nextWeek->format('d.m.Y'));
    }

    public function test_a_mid_week_url_resolves_to_the_containing_week(): void
    {
        $team = $this->tenant();

        $weekStart = app(WeekService::class)->start($team, CarbonImmutable::now());
        $midWeek = $weekStart->addDays(3);

        $this->actingAs($this->member($team))
            ->get(route('calendar.show', ['date' => $midWeek->toDateString()]))
            ->assertOk()
            ->assertSee($weekStart->format('d.m.Y'));
    }

    public function test_signed_up_people_are_listed_on_their_day(): void
    {
        $team = $this->tenant();
        $position = Position::factory()->create(['team_id' => $team->id, 'name' => 'Pokladňa', 'code' => 'POK']);
        $user = $this->member($team);
        $weekStart = app(WeekService::class)->start($team, CarbonImmutable::now());

        Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'position_id' => $position->id,
            'date' => $weekStart->addDay()->toDateString(),
            'note' => 'od 15:00',
        ]);

        $this->actingAs($user)
            ->get(route('calendar.show', ['date' => $weekStart->toDateString()]))
            ->assertOk()
            ->assertSee((string) $user)
            ->assertSee('od 15:00');
    }

    public function test_subory_button_is_hidden_for_employee_when_no_visible_media(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $weekStart = app(WeekService::class)->start($team, CarbonImmutable::now());

        // 1. No media at all
        $this->actingAs($user)
            ->get(route('calendar.show', ['date' => $weekStart->toDateString()]))
            ->assertOk()
            ->assertDontSee('Súbory');

        // 2. Media exists but is hidden
        Media::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'week_start' => $weekStart->toDateString(),
            'disk' => 'local',
            'path' => 'media/hidden.pdf',
            'filename' => 'hidden.pdf',
            'original_name' => 'hidden.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'is_visible' => false,
        ]);

        $this->actingAs($user)
            ->get(route('calendar.show', ['date' => $weekStart->toDateString()]))
            ->assertOk()
            ->assertDontSee('Súbory');
    }

    public function test_subory_button_is_visible_for_employee_when_visible_media_exists(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);
        $weekStart = app(WeekService::class)->start($team, CarbonImmutable::now());

        Media::create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'week_start' => $weekStart->toDateString(),
            'disk' => 'local',
            'path' => 'media/visible.pdf',
            'filename' => 'visible.pdf',
            'original_name' => 'visible.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'is_visible' => true,
        ]);

        $this->actingAs($user)
            ->get(route('calendar.show', ['date' => $weekStart->toDateString()]))
            ->assertOk()
            ->assertSee('Súbory');
    }

    public function test_subory_button_is_always_visible_for_manager(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $weekStart = app(WeekService::class)->start($team, CarbonImmutable::now());

        // No media exists, but manager can upload files
        $this->actingAs($manager)
            ->get(route('calendar.show', ['date' => $weekStart->toDateString()]))
            ->assertOk()
            ->assertSee('Súbory');
    }
}
