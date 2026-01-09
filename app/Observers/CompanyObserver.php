<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\CalendarType;
use App\Enums\CalendarVisibility;
use App\Models\Company;

class CompanyObserver
{
    public function created(Company $company): void
    {
        $company->calendars()->create([
            'name' => __('calendar.default_calendar_name'),
            'type' => CalendarType::Default->value,
            'visibility' => CalendarVisibility::CompanyWide->value,
            'is_default' => true,
            'color' => '#3b82f6',
        ]);
    }
}
