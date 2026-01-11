<?php

namespace Tests\Unit\Services;

use App\Services\CalendarService;
use Carbon\Carbon;
use Tests\TestCase;

class CalendarServiceDateTest extends TestCase
{
    private CalendarService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CalendarService();
    }

    public function test_get_month_grid_generates_correct_dates_for_april_2026()
    {
        // April 2026
        // 1st is Wednesday.
        // Start of grid (Mon) should be March 30.
        // End of grid (Sun) should be May 3 (Since April 30 is Thursday, end of week is May 3).
        
        $date = Carbon::parse('2026-04-11');
        $grid = $this->service->getMonthGrid($date);

        $this->assertNotEmpty($grid);

        $firstDay = $grid[0];
        $lastDay = end($grid);

        // Check bounds
        $this->assertEquals('2026-03-30', $firstDay['date']->toDateString(), 'First day of grid should be March 30 (Start of week for April 1st)');
        $this->assertEquals('2026-05-03', $lastDay['date']->toDateString(), 'Last day of grid should be May 3 (End of week for April 30th)');

        // Check a day in previous month (March 31)
        $march31 = collect($grid)->first(fn($d) => $d['date']->toDateString() === '2026-03-31');
        $this->assertNotNull($march31);
        $this->assertFalse($march31['isCurrentMonth'], 'March 31 should not be current month for April view');

        // Check a day in current month (April 1)
        $april1 = collect($grid)->first(fn($d) => $d['date']->toDateString() === '2026-04-01');
        $this->assertNotNull($april1);
        $this->assertTrue($april1['isCurrentMonth'], 'April 1 should be current month');

        // Check a day in next month (May 1)
        $may1 = collect($grid)->first(fn($d) => $d['date']->toDateString() === '2026-05-01');
        $this->assertNotNull($may1);
        $this->assertFalse($may1['isCurrentMonth'], 'May 1 should not be current month');
    }

    public function test_get_month_grid_respects_input_date_month()
    {
        // User reported: URL=April, Label=March.
        // Let's verify if we pass April, result says isCurrentMonth=true for April days.

        $date = Carbon::parse('2026-04-15'); 
        $grid = $this->service->getMonthGrid($date);

        $april15 = collect($grid)->first(fn($d) => $d['date']->toDateString() === '2026-04-15');
        
        $this->assertTrue($april15['isCurrentMonth']);
        $this->assertEquals(15, $april15['day']);
    }
}
