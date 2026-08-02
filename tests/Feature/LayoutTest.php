<?php

namespace Tests\Feature;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Livewire only auto-injects its bundle (which carries Alpine) on pages that rendered a
     * component, so a page without one silently loses every x-data in the navigation.
     */
    public function test_a_page_without_a_livewire_component_still_loads_alpine(): void
    {
        $this->get(route('help'))
            ->assertOk()
            ->assertSee('/livewire/livewire', false);
    }

    /**
     * The navigation gates its entries on team-scoped permissions, so a public page that never
     * establishes the team renders an admin's dropdown as if they were a plain employee.
     */
    public function test_a_public_page_gates_the_navigation_against_the_current_team(): void
    {
        $team = $this->tenant();

        $this->actingAs($this->member($team, Role::Admin))
            ->get(route('help'))
            ->assertOk()
            ->assertSee('Správa kina')
            ->assertSee('Pozície')
            ->assertSee('Používatelia');
    }

    /** A guest keeps reading the help page - it documents how to register. */
    public function test_a_guest_may_still_read_the_help_page(): void
    {
        $this->get(route('help'))
            ->assertOk()
            ->assertDontSee('Správa kina');
    }
}
