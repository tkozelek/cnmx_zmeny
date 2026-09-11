<?php

namespace Tests\Feature;

use App\Enums\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The one thing a plain Manager may not do: touch another Manager/HeadManager account, or
 * promote anyone into that tier. Everything else about "managing the app" (schedule, weeks,
 * accepting brigádnici) a Manager already does exactly like a HeadManager.
 */
class UserRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_head_manager_can_promote_a_manager_to_head_manager(): void
    {
        $team = $this->tenant();
        $headManager = $this->member($team, Role::HeadManager);
        $manager = $this->member($team, Role::Manager);

        $this->actingAs($headManager)
            ->put(route('admin.users.update', ['user' => $manager->id]), [
                'name' => $manager->name,
                'lastname' => $manager->lastname,
                'email' => $manager->email,
                'role' => Role::HeadManager->value,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($manager->fresh()->hasRole(Role::HeadManager->value));
    }

    public function test_a_manager_cannot_edit_another_managers_account(): void
    {
        $team = $this->tenant();
        $actingManager = $this->member($team, Role::Manager);
        $otherManager = $this->member($team, Role::Manager);

        $this->actingAs($actingManager)
            ->put(route('admin.users.update', ['user' => $otherManager->id]), [
                'name' => $otherManager->name,
                'lastname' => $otherManager->lastname,
                'email' => $otherManager->email,
                'role' => Role::Manager->value,
            ])
            ->assertForbidden();
    }

    public function test_a_manager_cannot_promote_a_brigadnik_to_manager(): void
    {
        $team = $this->tenant();
        $actingManager = $this->member($team, Role::Manager);
        $employee = $this->member($team, Role::Employee);

        $this->actingAs($actingManager)
            ->put(route('admin.users.update', ['user' => $employee->id]), [
                'name' => $employee->name,
                'lastname' => $employee->lastname,
                'email' => $employee->email,
                'role' => Role::Manager->value,
            ])
            ->assertForbidden();

        $this->assertTrue($employee->fresh()->hasRole(Role::Employee->value));
    }

    public function test_a_manager_can_edit_a_brigadnik_account(): void
    {
        $team = $this->tenant();
        $actingManager = $this->member($team, Role::Manager);
        $employee = $this->member($team, Role::Employee);

        $this->actingAs($actingManager)
            ->put(route('admin.users.update', ['user' => $employee->id]), [
                'name' => 'Nové',
                'lastname' => $employee->lastname,
                'email' => $employee->email,
                'role' => Role::Employee->value,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertSame('Nové', $employee->fresh()->name);
    }
}
