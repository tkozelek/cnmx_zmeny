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

    public function test_users_data_table_renders(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);

        Livewire::actingAs($user)
            ->test(UsersDataTable::class)
            ->assertOk();
    }

    public function test_absences_data_table_renders(): void
    {
        $team = $this->tenant();
        $user = $this->member($team);

        Livewire::actingAs($user)
            ->test(AbsencesDataTable::class)
            ->assertOk();
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
        $user = $this->member($team);

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
}
