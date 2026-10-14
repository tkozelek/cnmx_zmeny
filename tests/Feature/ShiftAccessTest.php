<?php

namespace Tests\Feature;

use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShiftAccessTest extends TestCase
{
    use RefreshDatabase;

    private function employee(): User
    {
        return User::factory()->create(['id_role' => config('constants.roles.brigadnik')]);
    }

    private function shiftFor(User $user, string $date = '2025-10-01'): Shift
    {
        return Shift::create(['user_id' => $user->id, 'date' => $date, 'start' => '08:00', 'end' => '16:00', 'break' => 30]);
    }

    public function test_shifts_api_is_gone(): void
    {
        $owner = $this->employee();
        $this->shiftFor($owner);

        $this->getJson("/api/shifts?user_id={$owner->id}&month=2025-10")->assertNotFound();
        $this->postJson("/api/shifts/bulk?user_id={$owner->id}", ['2025-10-02' => ['start' => '01:00', 'end' => '02:00']])
            ->assertNotFound();

        $this->actingAs($this->employee())
            ->getJson("/api/shifts?user_id={$owner->id}&month=2025-10")
            ->assertNotFound();

        $this->assertSame(1, Shift::count());
    }

    public function test_guest_cannot_read_or_write_shifts(): void
    {
        $this->get(route('hours.index'))->assertRedirect('/prihlasenie');
        $this->postJson(route('hours.store'), ['month' => 10, 'year' => 2025, 'workData' => []])
            ->assertRedirect('/prihlasenie');

        $this->assertSame(0, Shift::count());
    }

    public function test_user_cannot_read_another_users_shifts(): void
    {
        $owner = $this->employee();

        $this->actingAs($this->employee())->get(route('hours.show', $owner))->assertForbidden();
    }

    public function test_user_cannot_write_another_users_shifts(): void
    {
        $owner = $this->employee();
        $ownerShift = $this->shiftFor($owner);
        $other = $this->employee();

        $this->actingAs($other)->postJson(route('hours.store', ['user_id' => $owner->id]), [
            'user_id' => $owner->id,
            'month' => 10,
            'year' => 2025,
            'workData' => [['date' => '2025-10-01', 'start' => '01:00', 'end' => '02:00']],
        ])->assertOk();

        $this->assertSame('08:00', $ownerShift->fresh()->start->format('H:i'));
        $this->assertSame(1, $owner->shifts()->count());
        $this->assertSame(1, $other->shifts()->count());
    }

    public function test_owner_can_read_and_write_own_shifts(): void
    {
        $owner = $this->employee();
        $this->shiftFor($owner);

        $this->actingAs($owner)->get(route('hours.index'))->assertOk();

        $this->actingAs($owner)->postJson(route('hours.store'), [
            'month' => 10,
            'year' => 2025,
            'workData' => [['date' => '2025-10-01', 'start' => '09:00', 'end' => '17:00']],
        ])->assertOk();

        $this->assertSame('09:00', $owner->shifts()->first()->start->format('H:i'));
    }

    public function test_admin_can_read_any_users_shifts(): void
    {
        $owner = $this->employee();
        $admin = User::factory()->create(['id_role' => config('constants.roles.admin')]);

        $this->actingAs($admin)->get(route('hours.show', $owner))->assertOk();
    }
}
