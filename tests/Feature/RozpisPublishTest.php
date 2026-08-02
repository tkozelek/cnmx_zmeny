<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Exports\RozpisExport;
use App\Models\Assignment;
use App\Models\Position;
use App\Models\PositionSlot;
use App\Models\Team;
use App\Models\User;
use App\Services\RozpisService;
use App\Services\WeekService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

/**
 * The last phase of a week: releasing the finished rozpis, and reading it.
 *
 * The rule worth pinning down is who sees a draft. A half-built plan reaching the staff is worse
 * than no plan, because people act on it — so publication, not existence, is what opens the door.
 */
class RozpisPublishTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_manager_publishes_the_rozpis_and_employees_can_read_it(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $employee = $this->member($team);
        $weekStart = $this->weekStart($team);

        $this->lockWeek($team, $weekStart);
        $this->placeSomeone($team, $employee, $weekStart->addDay()->toDateString(), 'Bufet', '16:30:00');

        $this->actingAs($manager)
            ->post(route('rozpis.publish', ['date' => $weekStart->toDateString()]))
            ->assertRedirect(route('rozpis.show', ['date' => $weekStart->toDateString()]));

        $this->assertNotNull(
            $team->weekLocks()->first()->rozpis_published_at,
            'Publishing stamps the week lock.'
        );

        $this->actingAs($employee)
            ->get(route('rozpis.published', ['date' => $weekStart->toDateString()]))
            ->assertOk()
            ->assertSee('Bufet')
            ->assertSee('16:30')
            ->assertSee((string) $employee);
    }

    /** Until it is published, the plan is the manager's draft — nobody else is shown it. */
    public function test_an_unpublished_rozpis_is_not_shown_to_employees(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);
        $weekStart = $this->weekStart($team);

        $this->lockWeek($team, $weekStart);
        $this->placeSomeone($team, $employee, $weekStart->addDay()->toDateString(), 'Bufet', '16:30:00');

        $this->actingAs($employee)
            ->get(route('rozpis.published', ['date' => $weekStart->toDateString()]))
            ->assertRedirect(route('calendar.show', ['date' => $weekStart->toDateString()]))
            ->assertSessionHas('message', 'Rozpis na tento týždeň zatiaľ nie je zverejnený.');
    }

    /** The manager needs to see the draft to decide whether it is ready to release. */
    public function test_a_manager_may_preview_an_unpublished_rozpis(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $weekStart = $this->weekStart($team);

        $this->lockWeek($team, $weekStart);
        $this->placeSomeone($team, $this->member($team), $weekStart->addDay()->toDateString(), 'Bufet', '16:30:00');

        $this->actingAs($manager)
            ->get(route('rozpis.published', ['date' => $weekStart->toDateString()]))
            ->assertOk()
            ->assertSee('Pracovná verzia');
    }

    public function test_an_employee_cannot_publish(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);
        $weekStart = $this->weekStart($team);

        $this->lockWeek($team, $weekStart);

        $this->actingAs($employee)
            ->post(route('rozpis.publish', ['date' => $weekStart->toDateString()]))
            ->assertForbidden();

        $this->assertNull($team->weekLocks()->first()->rozpis_published_at);
    }

    /** Withdrawing puts the week back to draft without destroying the plan. */
    public function test_withdrawing_hides_the_rozpis_again(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $employee = $this->member($team);
        $weekStart = $this->weekStart($team);

        $this->lockWeek($team, $weekStart, published: true);
        $this->placeSomeone($team, $employee, $weekStart->addDay()->toDateString(), 'Bufet', '16:30:00');

        $this->actingAs($manager)
            ->delete(route('rozpis.unpublish', ['date' => $weekStart->toDateString()]))
            ->assertRedirect(route('rozpis.show', ['date' => $weekStart->toDateString()]));

        $this->assertNull($team->weekLocks()->first()->rozpis_published_at);

        $this->actingAs($employee)
            ->get(route('rozpis.published', ['date' => $weekStart->toDateString()]))
            ->assertRedirect(route('calendar.show', ['date' => $weekStart->toDateString()]));
    }

    /** An unlocked week has no rozpis to publish, so there is nothing to stamp. */
    public function test_publishing_an_unlocked_week_is_not_found(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $weekStart = $this->weekStart($team);

        $this->actingAs($manager)
            ->post(route('rozpis.publish', ['date' => $weekStart->toDateString()]))
            ->assertNotFound();
    }

    /**
     * Deliberately not Excel::fake() — faking skips the write, and the write is where the merged
     * title cells, the page break and the per-block styling would blow up. This is the only place
     * a bad PhpSpreadsheet call surfaces.
     */
    public function test_the_export_downloads_the_week_as_a_real_spreadsheet(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $weekStart = $this->weekStart($team);

        $this->lockWeek($team, $weekStart);
        $this->placeSomeone($team, $this->member($team), $weekStart->addDay()->toDateString(), 'Bufet', '16:30:00');

        $response = $this->actingAs($manager)
            ->get(route('rozpis.export', ['date' => $weekStart->toDateString()]))
            ->assertOk();

        $filename = sprintf(
            'Rozpis zmien brigádnikov_%s_%s - %s.xlsx',
            $team->name,
            $weekStart->format('d.m.'),
            $weekStart->addDays(6)->format('d.m.'),
        );

        // Symfony ASCII-folds the plain `filename` ("brigadnikov"), so the accented name only
        // survives in the RFC 5987 `filename*` — which is the one browsers actually use.
        $this->assertStringContainsString(
            "filename*=utf-8''".rawurlencode($filename),
            $response->headers->get('content-disposition'),
        );

        // A real xlsx is a zip archive; an empty or half-written file would not start with "PK".
        $content = $response->streamedContent();
        $this->assertStringStartsWith('PK', $content);

        // Both sheets, and the flat one actually carrying the week's shifts — the poster alone is
        // no good for looking anything up.
        $path = tempnam(sys_get_temp_dir(), 'rozpis').'.xlsx';
        file_put_contents($path, $content);

        try {
            $book = IOFactory::load($path);

            $this->assertSame(['Rozpis', 'Zoznam'], $book->getSheetNames());

            $list = $book->getSheetByName('Zoznam');

            $this->assertSame(
                ['Dátum', 'Deň', 'Skupina', 'Pozícia', 'Meno', 'Čas nástupu'],
                array_map(
                    fn (string $cell): mixed => $list->getCell($cell.'1')->getValue(),
                    ['A', 'B', 'C', 'D', 'E', 'F'],
                ),
            );

            $this->assertSame('Bufet', $list->getCell('D2')->getValue());
            $this->assertSame('16:30', $list->getCell('F2')->getValue());
        } finally {
            @unlink($path);
        }
    }

    public function test_an_employee_cannot_export(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);
        $weekStart = $this->weekStart($team);

        $this->lockWeek($team, $weekStart);

        $this->actingAs($employee)
            ->get(route('rozpis.export', ['date' => $weekStart->toDateString()]))
            ->assertForbidden();
    }

    /**
     * The grid the spreadsheet is written from — the part a reader cannot verify by eye, because
     * the day blocks sit at fixed coordinates and a wrong offset silently overwrites a neighbour.
     */
    public function test_the_export_grid_places_each_day_where_the_printed_sheet_expects_it(): void
    {
        $team = $this->tenant();
        $weekStart = $this->weekStart($team);

        $this->lockWeek($team, $weekStart);

        // Day 0 goes top-left, day 2 top-right, day 6 into the second page's right-hand block.
        $this->placeSomeone($team, $this->member($team), $weekStart->toDateString(), 'Bufet', '12:00:00');
        $this->placeSomeone($team, $this->member($team), $weekStart->addDays(2)->toDateString(), 'Pokladňa', '13:00:00');
        $this->placeSomeone($team, $this->member($team), $weekStart->addDays(6)->toDateString(), 'Uvádzač', '14:00:00');

        $grid = (new RozpisExport($team, $weekStart, app(RozpisService::class)))->array();

        // Sheet row 3 (index 2) is the first band: day 0 in column B, day 2 in column G.
        $this->assertSame($weekStart->format('d.m.Y'), $grid[2][1]);
        $this->assertSame($weekStart->addDays(2)->format('d.m.Y'), $grid[2][6]);

        // Sheet row 39 (index 38) starts page two, right-hand block: day 6.
        $this->assertSame($weekStart->addDays(6)->format('d.m.Y'), $grid[38][6]);

        // Two rows below a block's header sits its first position row: label then time.
        $this->assertSame('Bufet', $grid[4][1]);
        $this->assertSame('12:00', $grid[4][2]);
        $this->assertSame('Pokladňa', $grid[4][6]);
        $this->assertSame('13:00', $grid[4][7]);
    }

    /**
     * The vedúci is named once, in the day's heading — not again among the ordinary positions.
     *
     * They stay a real slot in the builder, so this asserts the *presentation* split rather than
     * the absence of the row: out of `rows`, into `manager`, still present in the flat sheet.
     */
    public function test_the_manager_appears_only_in_the_day_heading(): void
    {
        $team = $this->tenant();
        $weekStart = $this->weekStart($team);
        $date = $weekStart->toDateString();

        $this->lockWeek($team, $weekStart);

        $veduci = Position::factory()->create([
            'team_id' => $team->id, 'name' => 'Vedúci', 'code' => 'VED', 'is_manager' => true, 'sort_order' => 5,
        ]);
        $bufet = Position::factory()->create([
            'team_id' => $team->id, 'name' => 'Bufet', 'code' => null, 'is_manager' => false, 'sort_order' => 10,
        ]);

        $boss = $this->member($team, Role::Manager);
        $worker = $this->member($team);

        foreach ([[$veduci, $boss, '11:00:00'], [$bufet, $worker, '12:00:00']] as [$position, $user, $time]) {
            $slot = PositionSlot::factory()->forPosition($position)->on($date)->create(['start_time' => $time]);

            Assignment::factory()->create([
                'team_id' => $team->id, 'user_id' => $user->id,
                'position_id' => $position->id, 'position_slot_id' => $slot->getKey(),
                'date' => $date, 'start_time' => $time,
            ]);
        }

        $day = app(RozpisService::class)->plan($team, $weekStart)->first();

        $this->assertSame(['Bufet'], array_column($day['rows'], 'label'), 'VED is not one of the day rows.');
        $this->assertSame((string) $boss, $day['manager']);
        $this->assertSame(['VED'], array_column($day['managerRows'], 'label'));

        // Both slots are staffed, so the day counts as complete.
        $this->assertTrue($day['complete']);

        // And the poster body never prints them: only the manažér heading does.
        $grid = (new RozpisExport($team, $weekStart, app(RozpisService::class)))->array();

        $this->assertSame((string) $boss, $grid[2][3], 'The heading names the vedúci.');
        $this->assertSame('Bufet', $grid[4][1], 'The first body row is the bufet, not the vedúci.');
        $this->assertSame('', $grid[5][1], 'Nothing follows it.');
    }

    private function weekStart(Team $team): CarbonImmutable
    {
        return app(WeekService::class)->start($team, CarbonImmutable::now());
    }

    private function lockWeek(Team $team, CarbonImmutable $weekStart, bool $published = false): void
    {
        $team->weekLocks()->create([
            'week_start' => $weekStart->toDateString(),
            'rozpis_published_at' => $published ? now() : null,
        ]);
    }

    /** One person standing on one named position, which is what the read-only page renders. */
    private function placeSomeone(Team $team, User $user, string $date, string $position, string $time): void
    {
        $created = Position::factory()->create([
            'team_id' => $team->id,
            'name' => $position,
            'code' => null,
        ]);

        $slot = PositionSlot::factory()->forPosition($created)->on($date)->create(['start_time' => $time]);

        Assignment::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'position_id' => $created->id,
            'position_slot_id' => $slot->getKey(),
            'date' => $date,
            'start_time' => $time,
        ]);
    }
}
