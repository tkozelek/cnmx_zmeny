<?php

namespace Tests\Unit;

use App\Models\Shift;
// Tests\TestCase, not PHPUnit's: Eloquent resolves a connection while booting the model's
// traits, so even an unsaved Shift needs the framework up. No database is touched.
use Tests\TestCase;

/**
 * The overnight shift is the reason `shifts` stores DATETIME instead of a TIME pair, so it
 * is the one calculation worth pinning down.
 */
class ShiftTest extends TestCase
{
    public function test_worked_minutes_of_an_overnight_shift_is_positive(): void
    {
        // 21:00 -> 01:30 next day, 30 minutes unpaid break. The legacy TIME columns gave
        // end < start here and produced a negative duration.
        $shift = new Shift([
            'starts_at' => '2026-08-14 21:00:00',
            'ends_at' => '2026-08-15 01:30:00',
            'break_minutes' => 30,
        ]);

        $this->assertSame(240, $shift->workedMinutes());
    }

    public function test_an_overnight_shift_is_booked_to_the_day_it_started(): void
    {
        $shift = new Shift([
            'starts_at' => '2026-08-14 21:00:00',
            'ends_at' => '2026-08-15 01:30:00',
        ]);

        $this->assertSame('2026-08-14', $shift->payrollDate());
    }

    public function test_worked_minutes_subtracts_the_break(): void
    {
        $shift = new Shift([
            'starts_at' => '2026-08-14 12:00:00',
            'ends_at' => '2026-08-14 20:00:00',
            'break_minutes' => 45,
        ]);

        $this->assertSame(435, $shift->workedMinutes());
    }
}
