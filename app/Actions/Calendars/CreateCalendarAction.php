<?php

declare(strict_types=1);

namespace App\Actions\Calendars;

use App\Data\CalendarData;
use App\Models\Calendar;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateCalendarAction
{
    public function execute(CalendarData $data, User $user): Calendar
    {
        return DB::transaction(function () use ($data, $user) {
            $calendar = Calendar::create([
                'company_id' => $user->currentCompany()->id,
                'name' => $data->name,
                'color' => $data->color,
                'type' => $data->type,
                'visibility' => $data->visibility,
                'is_default' => false,
            ]);

            // Oluşturan kişiyi owner olarak pivot'a ekle
            $calendar->users()->attach($user->id, ['role' => 'owner']);

            return $calendar;
        });
    }
}
