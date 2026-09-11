<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\UsersDataTable;
use Livewire\Livewire;
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

    /**
     * A cinema must always keep somebody who can administer it.
     *
     * Promoting *into* the hlavný manažér tier needs `user.manage-managers`, which only that tier
     * holds - so a cinema that loses its last one cannot appoint another. There is no way back
     * short of database access, and a head manager demoting themselves is the likeliest route in.
     */
    public function test_the_last_head_manager_cannot_be_demoted(): void
    {
        $team = $this->tenant();
        $headManager = $this->member($team, Role::HeadManager);

        $this->actingAs($headManager)
            ->put(route('admin.users.update', ['user' => $headManager->id]), [
                'name' => $headManager->name,
                'lastname' => $headManager->lastname,
                'email' => $headManager->email,
                'role' => Role::Employee->value,
            ])
            ->assertForbidden();

        $this->assertTrue($headManager->fresh()->hasRole(Role::HeadManager->value));
    }

    public function test_the_last_head_manager_cannot_be_deactivated(): void
    {
        $team = $this->tenant();
        $first = $this->member($team, Role::HeadManager);
        $second = $this->member($team, Role::HeadManager);

        // Two of them, so this one is expendable.
        $this->actingAs($first)
            ->delete(route('admin.users.destroy', ['user' => $second->id]))
            ->assertRedirect(route('admin.users.index'));

        $this->assertFalse($second->fresh()->is_active);

        // Now $first is the only one left, and nobody may deactivate them.
        $manager = $this->member($team, Role::Manager);

        $this->actingAs($manager)
            ->delete(route('admin.users.destroy', ['user' => $first->id]))
            ->assertForbidden();

        $this->assertTrue($first->fresh()->is_active);
    }

    /** Once there are two, either may be demoted - the rule is about the last one, not the tier. */
    public function test_a_head_manager_can_be_demoted_while_another_remains(): void
    {
        $team = $this->tenant();
        $staying = $this->member($team, Role::HeadManager);
        $leaving = $this->member($team, Role::HeadManager);

        $this->actingAs($staying)
            ->put(route('admin.users.update', ['user' => $leaving->id]), [
                'name' => $leaving->name,
                'lastname' => $leaving->lastname,
                'email' => $leaving->email,
                'role' => Role::Employee->value,
            ])
            ->assertRedirect(route('admin.users.index'));

        $this->assertTrue($leaving->fresh()->hasRole(Role::Employee->value));
    }

    /**
     * A blocked or unapproved head manager does not count as cover: the cinema still has nobody
     * who can log in and administer it.
     */
    public function test_a_blocked_head_manager_does_not_count_as_the_one_who_remains(): void
    {
        $team = $this->tenant();
        $active = $this->member($team, Role::HeadManager);
        $blocked = $this->member($team, Role::HeadManager);
        $blocked->update(['is_active' => false]);

        $this->actingAs($active)
            ->put(route('admin.users.update', ['user' => $active->id]), [
                'name' => $active->name,
                'lastname' => $active->lastname,
                'email' => $active->email,
                'role' => Role::Employee->value,
            ])
            ->assertForbidden();

        $this->assertTrue($active->fresh()->hasRole(Role::HeadManager->value));
    }

    /**
     * The approve/deny buttons were a way around the whole hierarchy: `user.approve` is a
     * permission a plain manager holds, and refusing a membership unapproves and deactivates.
     */
    public function test_a_manager_cannot_revoke_a_head_managers_membership(): void
    {
        $team = $this->tenant();
        $manager = $this->member($team, Role::Manager);
        $headManager = $this->member($team, Role::HeadManager);

        Livewire::actingAs($manager)
            ->test(UsersDataTable::class)
            ->call('deny', $headManager->id)
            ->assertStatus(403);

        $headManager->forgetApprovedTeamsCache();

        $this->assertTrue($headManager->fresh()->is_active);
        $this->assertTrue($headManager->fresh()->isApprovedIn($team));
    }
}
