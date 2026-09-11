<?php

namespace Tests\Unit\Helpers;

use CodeIgniter\Test\CIUnitTestCase;

/**
 * @internal
 */
final class DateHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('date_helper');
    }

    public function testDate2IdFunctionExists(): void
    {
        $this->assertTrue(function_exists('date2ID'));
    }

    public function testDate2IdReturnsDashForEmpty(): void
    {
        $this->assertSame('-', date2ID(null));
        $this->assertSame('-', date2ID(''));
    }

    public function testDate2IdFullFormatIndonesian(): void
    {
        // 2026-09-10 is Thursday (Kamis), September (September)
        $formatted = date2ID('2026-09-10 15:30:00', 'full');
        $this->assertStringContainsString('Kamis', $formatted);
        $this->assertStringContainsString('10 September 2026', $formatted);
        $this->assertStringContainsString('15:30', $formatted);
        $this->assertStringContainsString('WIB', $formatted);
    }

    public function testDate2IdMediumFormat(): void
    {
        $formatted = date2ID('2026-09-10 15:30:00', 'medium');
        $this->assertStringContainsString('10 Sep 2026', $formatted);
        $this->assertStringContainsString('15:30', $formatted);
    }

    public function testDate2IdShortFormat(): void
    {
        $formatted = date2ID('2026-09-10 15:30:00', 'short');
        $this->assertSame('10 Sep 2026', $formatted);
    }

    public function testDate2IdDayOnlyFormat(): void
    {
        $formatted = date2ID('2026-09-10 15:30:00', 'day_only');
        $this->assertSame('Kamis', $formatted);
    }

    public function testDate2IdDateOnlyFormat(): void
    {
        $formatted = date2ID('2026-09-10 15:30:00', 'date_only');
        $this->assertSame('10 September 2026', $formatted);
    }
}
