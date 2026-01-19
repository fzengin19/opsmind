<?php

declare(strict_types=1);

namespace App\Actions\Calendars;

use App\Enums\CalendarType;
use App\Models\Calendar;
use DomainException;
use Illuminate\Support\Facades\DB;

class DeleteCalendarAction
{
    public function execute(Calendar $calendar): void
    {
        // Güvenlik kontrolü
        if ($calendar->is_default) {
            throw new DomainException(__('calendar.cannot_delete_default'));
        }

        if ($calendar->type === CalendarType::Personal) {
            throw new DomainException(__('calendar.cannot_delete_personal'));
        }

        DB::transaction(function () use ($calendar) {
            // Randevuları varsayılan takvime taşı
            $defaultCalendar = $calendar->company->defaultCalendar();

            if ($defaultCalendar) {
                $calendar->appointments()->update([
                    'calendar_id' => $defaultCalendar->id,
                ]);
            }

            // calendar_user pivot'ları cascade ile silinir (FK constraint)
            $calendar->delete();
        });
    }
}
