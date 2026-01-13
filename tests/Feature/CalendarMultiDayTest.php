<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Calendar;
use App\Models\Company;
use App\Models\User;
use App\Services\CalendarService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarMultiDayTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_day_event_spans_multiple_days_with_correct_positions()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $company->users()->attach($user);
        $calendar = Calendar::factory()->create(['company_id' => $company->id]);

        // Create a 3-day event: Mon, Tue, Wed
        $start = Carbon::parse('2024-01-01 10:00:00'); // Monday
        $end = Carbon::parse('2024-01-03 12:00:00');   // Wednesday

        Appointment::factory()->create([
            'company_id' => $company->id,
            'calendar_id' => $calendar->id,
            'title' => 'Multi Day Event',
            'start_at' => $start,
            'end_at' => $end,
        ]);

        $service = app(CalendarService::class);
        
        // Fetch for January
        $events = $service->getAppointmentsForMonth(Carbon::parse('2024-01-01'), $company->id);

        // Check Monday (Start)
        $mon = $events['2024-01-01'][0];
        $this->assertEquals('start', $mon['position']);
        $this->assertEquals('Multi Day Event', $mon['title']);

        // Check Tuesday (Middle)
        $tue = $events['2024-01-02'][0];
        $this->assertEquals('middle', $tue['position']);

        // Check Wednesday (End)
        $wed = $events['2024-01-03'][0];
        $this->assertEquals('end', $wed['position']);
    }

    public function test_single_day_event_has_single_position()
    {
        $company = Company::factory()->create();
        $calendar = Calendar::factory()->create(['company_id' => $company->id]);

        Appointment::factory()->create([
            'company_id' => $company->id,
            'calendar_id' => $calendar->id,
            'start_at' => '2024-01-05 10:00:00',
            'end_at' => '2024-01-05 11:00:00',
        ]);

        $service = app(CalendarService::class);
        $events = $service->getAppointmentsForMonth(Carbon::parse('2024-01-01'), $company->id);

        $day = $events['2024-01-05'][0];
        $this->assertEquals('single', $day['position']);
    }

    public function test_get_day_agenda_includes_spanning_events()
    {
        $company = Company::factory()->create();
        $calendar = Calendar::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create();
        $company->users()->attach($user);

        // Create 3-day event (14th - 16th)
        $start = Carbon::parse('2024-01-14 00:00:00');
        $end = Carbon::parse('2024-01-16 23:59:59');

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'calendar_id' => $calendar->id,
            'start_at' => $start,
            'end_at' => $end,
            'all_day' => true,
        ]);

        $service = app(CalendarService::class);

        // Test Middle Day (15th) using getDayAgenda
        $agenda = $service->getDayAgenda(Carbon::parse('2024-01-15'), $company->id, [$calendar->id]);

        // It should contain the event
        $found = $agenda->where('type', 'event')->first(function ($item) use ($appointment) {
            return $item['data']->id === $appointment->id;
        });

        $this->assertNotNull($found, 'Event spanning multiple days should appear on middle day agenda');
    }
}
