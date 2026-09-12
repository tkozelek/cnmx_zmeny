<?php

namespace Tests\Feature;

use App\Enums\AbsenceStatus;
use App\Enums\Role;
use App\Livewire\AbsencesDataTable;
use App\Livewire\UsersDataTable;
use App\Models\Absence;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DataTableTest extends TestCase
{
    use RefreshDatabase;

    /** As a manager: the component is only ever rendered behind that permission. */
    public function test_users_data_table_renders(): void
    {
        $team = $this->tenant();

        Livewire::actingAs($this->member($team, Role::Manager))
            ->test(UsersDataTable::class)
            ->assertOk();
    }

    /** The all-staff table, so a manager - MyAbsencesDataTable is the everyone-facing one. */
    public function test_absences_data_table_renders(): void
    {
        $team = $this->tenant();

        Livewire::actingAs($this->member($team, Role::Manager))
            ->test(AbsencesDataTable::class)
            ->assertOk();
    }

    /**
     * Both all-staff tables refuse an employee on their own, not just in the Blade that renders
     * them. A view-level @can is the only thing that has ever stood between an employee and
     * every colleague's account and absence history.
     */
    public function test_the_all_staff_tables_refuse_an_employee(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);

        // Livewire's test helper renders the AuthorizationException as a 403 rather than
        // throwing it, so the status is what there is to assert.
        foreach ([UsersDataTable::class, AbsencesDataTable::class] as $component) {
            Livewire::actingAs($employee)->test($component)->assertStatus(403);
        }
    }

    /**
     * The Stav/Akcie columns read status/team_id/user_id straight off $row rather than through
     * a registered Column, so the package's column-based SELECT projection silently drops those
     * fields unless explicitly re-added - which made every cancelled absence render as "Aktívna"
     * with no action buttons at all.
     */
    public function test_absences_data_table_shows_cancelled_status_and_delete_action(): void
    {
        $team = $this->tenant();
        $user = $this->member($team, Role::Manager);

        Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $user->id,
            'date_from' => CarbonImmutable::now()->addDay()->toDateString(),
            'date_to' => CarbonImmutable::now()->addDays(4)->toDateString(),
            'status' => AbsenceStatus::Cancelled,
        ]);

        Livewire::actingAs($user)
            ->test(AbsencesDataTable::class)
            ->assertSee('Deaktivovaná')
            ->assertDontSee('Aktívna')
            ->assertSeeHtml('fa-trash');
    }

    /**
     * The admin table passes asManager: true, so an admin deleting their OWN inactive absence
     * from there skips the retention wait too - unlike "Moje absencie" or the plain
     * absences.destroy route, which still enforce it even for an admin's own record.
     */
    public function test_admin_can_instantly_delete_their_own_inactive_absence_from_the_admin_table(): void
    {
        $team = $this->tenant();
        $admin = $this->member($team, Role::HeadManager);

        $absence = Absence::factory()->create([
            'team_id' => $team->id,
            'user_id' => $admin->id,
            'date_from' => CarbonImmutable::now()->subDays(4)->toDateString(),
            'date_to' => CarbonImmutable::now()->subDays(2)->toDateString(),
        ]);
        $absence->timestamps = false;
        $absence->forceFill(['created_at' => CarbonImmutable::now()->subDays(20)])->save();

        Livewire::actingAs($admin)
            ->test(AbsencesDataTable::class)
            ->call('deleteAbsence', $absence->id)
            ->assertHasNoErrors();

        $this->assertModelMissing($absence);
    }

    public function test_users_data_table_filter_configuration_matches_absences(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);

        $test = Livewire::actingAs($manager)->test(UsersDataTable::class);
        $component = $test->instance();

        $this->assertTrue($component->isFilterLayoutSlideDown());
        $this->assertFalse($component->getFilterPillsStatus());
        $this->assertFalse($component->getColumnSelectStatus());
        $this->assertSame(10, $component->getPerPage());
    }
}
