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

class CalendarFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_service_filtering()
    {
        $company = Company::factory()->create();
        $user = User::factory()->create();
        $company->users()->attach($user);
        $service = app(CalendarService::class);
        $date = Carbon::now();

        // Create 2 calendars
        $calA = Calendar::factory()->create(['company_id' => $company->id, 'name' => 'Cal A']);
        $calB = Calendar::factory()->create(['company_id' => $company->id, 'name' => 'Cal B']);

        // Create appointments
        Appointment::factory()->create(['calendar_id' => $calA->id, 'company_id' => $company->id, 'start_at' => $date->copy()->hour(10)]);
        Appointment::factory()->create(['calendar_id' => $calB->id, 'company_id' => $company->id, 'start_at' => $date->copy()->hour(11)]);

        // 1. Filter by Cal A
        $eventsA = $service->getAppointmentsForWeek($date, $company->id, [$calA->id]);
        $this->assertCount(1, $eventsA);
        $this->assertEquals($calA->id, $eventsA->first()['calendarId']);

        // 2. Filter by Cal B
        $eventsB = $service->getAppointmentsForWeek($date, $company->id, [$calB->id]);
        $this->assertCount(1, $eventsB);
        $this->assertEquals($calB->id, $eventsB->first()['calendarId']);

        // 3. Filter by Both
        $eventsBoth = $service->getAppointmentsForWeek($date, $company->id, [$calA->id, $calB->id]);
        $this->assertCount(2, $eventsBoth);

        // 4. Filter by None (Empty array) -> Should return 0
        $eventsNone = $service->getAppointmentsForWeek($date, $company->id, []);
        $this->assertCount(0, $eventsNone); // Assuming logic whereIn array empty returns nothing or whereIn behavior
        
        // 5. No Filter (Null) -> Should return All (legacy behavior check)
        // Note: index.blade.php passes visibleCalendarIds which might be all.
        // But if I pass null explicitly?
        $eventsNull = $service->getAppointmentsForWeek($date, $company->id, null);
        $this->assertCount(2, $eventsNull);
    }
}
