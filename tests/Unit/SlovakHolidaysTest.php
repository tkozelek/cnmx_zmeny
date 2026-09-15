<?php

namespace Tests\Unit;

use App\Services\SlovakHolidays;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SlovakHolidaysTest extends TestCase
{
    public static function holidayProvider(): array
    {
        return [
            'New Year / independence day' => ['2026-01-01', true],
            'Epiphany' => ['2026-01-06', true],
            'Good Friday 2026 (Easter Sunday is 5.4.)' => ['2026-04-03', true],
            'Easter Monday 2026' => ['2026-04-06', true],
            'Easter Sunday itself is not a public holiday' => ['2026-04-05', false],
            'Labour Day' => ['2026-05-01', true],
            'Christmas Eve' => ['2026-12-24', true],
            'Christmas Day' => ['2026-12-25', true],
            'St. Stephen\'s Day' => ['2026-12-26', true],
            'ordinary Monday' => ['2026-06-15', false],
            'day after New Year' => ['2026-01-02', false],
        ];
    }

    #[DataProvider('holidayProvider')]
    public function test_is_holiday(string $date, bool $expected): void
    {
        $this->assertSame($expected, SlovakHolidays::isHoliday(CarbonImmutable::parse($date)));
    }

    /**
     * Good Friday and Easter Monday move every year with Easter Sunday - the one part of this
     * calendar that isn't a fixed month/day lookup.
     */
    public function test_easter_based_holidays_track_easter_sunday_across_years(): void
    {
        // Easter Sunday: 2024-03-31, 2025-04-20, 2027-03-28.
        $this->assertTrue(SlovakHolidays::isHoliday(CarbonImmutable::parse('2024-03-29'))); // Good Friday
        $this->assertTrue(SlovakHolidays::isHoliday(CarbonImmutable::parse('2024-04-01'))); // Easter Monday
        $this->assertTrue(SlovakHolidays::isHoliday(CarbonImmutable::parse('2025-04-18'))); // Good Friday
        $this->assertTrue(SlovakHolidays::isHoliday(CarbonImmutable::parse('2025-04-21'))); // Easter Monday
        $this->assertTrue(SlovakHolidays::isHoliday(CarbonImmutable::parse('2027-03-26'))); // Good Friday
        $this->assertTrue(SlovakHolidays::isHoliday(CarbonImmutable::parse('2027-03-29'))); // Easter Monday
    }
}
