<?php

namespace Tests\Unit\Services;

use App\Models\Appointment;
use App\Models\Calendar;
use App\Models\Company;
use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarServiceOverlapTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_overlap_width_and_left_correctly()
    {
        // Setup
        $company = Company::factory()->create();
        $calendar = Calendar::factory()->create(['company_id' => $company->id]);
        $service = new CalendarService();

        $baseDate = Carbon::create(2024, 1, 1, 9, 0, 0); // Monday

        // Create 3 overlapping appointments on Monday
        // Appt 1: 09:00 - 10:00
        Appointment::factory()->create([
            'company_id' => $company->id,
            'calendar_id' => $calendar->id,
            'start_at' => $baseDate->copy(),
            'end_at' => $baseDate->copy()->addHour(),
            'title' => 'Appt 1'
        ]);

        // Appt 2: 09:15 - 10:15
        Appointment::factory()->create([
            'company_id' => $company->id,
            'calendar_id' => $calendar->id,
            'start_at' => $baseDate->copy()->addMinutes(15),
            'end_at' => $baseDate->copy()->addMinutes(75),
            'title' => 'Appt 2'
        ]);

        // Appt 3: 09:30 - 10:30
        Appointment::factory()->create([
            'company_id' => $company->id,
            'calendar_id' => $calendar->id,
            'start_at' => $baseDate->copy()->addMinutes(30),
            'end_at' => $baseDate->copy()->addMinutes(90),
            'title' => 'Appt 3'
        ]);

        // Non-overlapping Appt 4: 11:00 - 12:00
        Appointment::factory()->create([
            'company_id' => $company->id,
            'calendar_id' => $calendar->id,
            'start_at' => $baseDate->copy()->addHours(2),
            'end_at' => $baseDate->copy()->addHours(3),
            'title' => 'Appt 4'
        ]);

        // Execute
        $events = $service->getAppointmentsForWeek($baseDate, $company->id);

        // Assert
        $this->assertCount(4, $events);

        $appt1 = $events->firstWhere('title', 'Appt 1');
        $appt2 = $events->firstWhere('title', 'Appt 2');
        $appt3 = $events->firstWhere('title', 'Appt 3');
        $appt4 = $events->firstWhere('title', 'Appt 4');

        // Check overlaps (Appt 1, 2, 3 should share width)
        // With simple clustering, they overlap transitively, so count=3
        $this->assertEquals(33.333333333333336, $appt1['width']);
        $this->assertEquals(0, $appt1['left']); // 0 * 33.33

        $this->assertEquals(33.333333333333336, $appt2['width']);
        $this->assertEquals(33.333333333333336, $appt2['left']); // 1 * 33.33

        $this->assertEquals(33.333333333333336, $appt3['width']);
        $this->assertEquals(66.66666666666667, $appt3['left']); // 2 * 33.33

        // Non-overlapping should be full width
        $this->assertEquals(100, $appt4['width']);
        $this->assertEquals(0, $appt4['left']);
    }
}
