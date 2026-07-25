<?php

namespace Tests\Feature;

use App\Livewire\AbsencesDataTable;
use App\Livewire\UsersDataTable;
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
}
