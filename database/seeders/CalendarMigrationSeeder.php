<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CalendarType;
use App\Enums\CalendarVisibility;
use App\Models\Appointment;
use App\Models\Company;
use Illuminate\Database\Seeder;

class CalendarMigrationSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::whereDoesntHave('calendars')->get();

        foreach ($companies as $company) {
            $calendar = $company->calendars()->create([
                'name' => __('calendar.default_calendar_name'),
                'type' => CalendarType::Default->value,
                'visibility' => CalendarVisibility::CompanyWide->value,
                'is_default' => true,
                'color' => '#3b82f6',
            ]);

            // Mevcut randevuları bu takvime bağla
            $updated = Appointment::where('company_id', $company->id)
                ->whereNull('calendar_id')
                ->update(['calendar_id' => $calendar->id]);

            $this->command->info("✅ {$company->name}: Takvim oluşturuldu, {$updated} randevu bağlandı.");
        }
    }
}
