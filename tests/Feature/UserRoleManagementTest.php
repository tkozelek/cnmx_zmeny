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

    public function test_a_manager_cannot_edit_a_head_managers_account(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $headManager = $this->member($team, Role::HeadManager);

        $this->actingAs($manager)
            ->get(route('admin.users.edit', ['user' => $headManager->id]))
            ->assertForbidden();

        $this->actingAs($manager)
            ->put(route('admin.users.update', ['user' => $headManager->id]), [
                'name' => 'Prepisane',
                'lastname' => $headManager->lastname,
                'email' => $headManager->email,
                'role' => Role::Employee->value,
            ])
            ->assertForbidden();

        $this->assertSame($headManager->name, $headManager->fresh()->name);
        $this->assertTrue($headManager->fresh()->hasRole(Role::HeadManager->value));
    }

    /** Not even downwards: demoting a peer is still reaching into the manager tier. */
    public function test_a_manager_cannot_demote_another_manager(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $peer = $this->member($team, Role::Manager);

        $this->actingAs($manager)
            ->put(route('admin.users.update', ['user' => $peer->id]), [
                'name' => $peer->name,
                'lastname' => $peer->lastname,
                'email' => $peer->email,
                'role' => Role::Employee->value,
            ])
            ->assertForbidden();

        $this->assertTrue($peer->fresh()->hasRole(Role::Manager->value));
    }

    public function test_a_manager_cannot_promote_anyone_to_head_manager(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $employee = $this->member($team);

        $this->actingAs($manager)
            ->put(route('admin.users.update', ['user' => $employee->id]), [
                'name' => $employee->name,
                'lastname' => $employee->lastname,
                'email' => $employee->email,
                'role' => Role::HeadManager->value,
            ])
            ->assertForbidden();

        $this->assertTrue($employee->fresh()->hasRole(Role::Employee->value));
    }

    public function test_a_manager_cannot_deactivate_another_manager_or_a_head_manager(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);

        foreach ([Role::Manager, Role::HeadManager] as $role) {
            $target = $this->member($team, $role);

            $this->actingAs($manager)
                ->delete(route('admin.users.destroy', ['user' => $target->id]))
                ->assertForbidden();

            $this->assertTrue($target->fresh()->is_active, $role->value.' was deactivated by a manager.');
        }
    }

    /** Creating into the tier is the same gate as promoting into it. */
    public function test_a_manager_cannot_create_a_manager_but_a_head_manager_can(): void
    {
        $team = $this->tenant();

        $payload = fn (string $email, Role $role): array => [
            'name' => 'Nova',
            'lastname' => 'Osoba',
            'email' => $email,
            'role' => $role->value,
        ];

        $this->actingAs($this->member($team, Role::Manager))
            ->post(route('admin.users.store'), $payload('by-manager@kino.test', Role::Manager))
            ->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'by-manager@kino.test']);

        $this->actingAs($this->member($team, Role::HeadManager))
            ->post(route('admin.users.store'), $payload('by-head@kino.test', Role::Manager));

        $this->assertDatabaseHas('users', ['email' => 'by-head@kino.test']);
    }

    /** A manager may still hire brigádnici - that is most of the job. */
    public function test_a_manager_can_create_a_brigadnik(): void
    {
        $team = $this->tenant();

        $this->actingAs($this->member($team, Role::Manager))
            ->post(route('admin.users.store'), [
                'name' => 'Nova',
                'lastname' => 'Brigadnicka',
                'email' => 'brigadnik@kino.test',
                'role' => Role::Employee->value,
            ]);

        $this->assertDatabaseHas('users', ['email' => 'brigadnik@kino.test']);
    }

    /**
     * The form only offers what the actor may actually assign. The policy is the real gate -
     * UpdateUserRequest accepts any role in the enum - but a dropdown listing roles that will
     * be refused on submit is its own bug.
     */
    public function test_the_role_dropdown_offers_a_manager_only_the_brigadnik_role(): void
    {
        $team = $this->tenant();
        $employee = $this->member($team);

        $this->actingAs($this->member($team, Role::Manager))
            ->get(route('admin.users.edit', ['user' => $employee->id]))
            ->assertOk()
            ->assertSee(Role::Employee->label())
            ->assertDontSee(Role::Manager->label())
            ->assertDontSee(Role::HeadManager->label());

        $this->actingAs($this->member($team, Role::HeadManager))
            ->get(route('admin.users.edit', ['user' => $employee->id]))
            ->assertOk()
            ->assertSee(Role::Manager->label())
            ->assertSee(Role::HeadManager->label());
    }
}
