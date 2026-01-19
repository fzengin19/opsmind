<?php

declare(strict_types=1);

namespace App\Actions\Calendars;

use App\Data\CalendarData;
use App\Enums\CalendarType;
use App\Models\Calendar;

class UpdateCalendarAction
{
    public function execute(Calendar $calendar, CalendarData $data): Calendar
    {
        $updateData = [
            'name' => $data->name,
            'color' => $data->color,
        ];

        // Visibility sadece Team/Resource için güncellenebilir
        if (in_array($calendar->type, [CalendarType::Team, CalendarType::Resource], true)) {
            $updateData['visibility'] = $data->visibility;
        }

        // type ve is_default ASLA güncellenmez

        $calendar->update($updateData);

        return $calendar->fresh();
    }
}
