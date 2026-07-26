<?php

namespace Tests\Feature;

use App\Enums\AbsenceStatus;
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
     * fields unless explicitly re-added — which made every cancelled absence render as "Aktívna"
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
}
