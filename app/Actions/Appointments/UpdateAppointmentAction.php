<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Data\AppointmentData;
use App\Enums\AttendeeStatus;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

class UpdateAppointmentAction
{
    public function execute(Appointment $appointment, AppointmentData $data): Appointment
    {
        return DB::transaction(function () use ($appointment, $data) {
            $appointment->update([
                'calendar_id' => $data->calendar_id,
                'title' => $data->title,
                'type' => $data->type,
                'start_at' => $data->start_at,
                'end_at' => $data->end_at,
                'all_day' => $data->all_day,
                'location' => $data->location,
                'description' => $data->description,
            ]);

            // Attendees güncelle
            $currentUserIds = $appointment->attendees()
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->toArray();

            $newUserIds = $data->attendee_user_ids ?? [];

            // Kaldırılanları sil (created_by hariç)
            $toRemove = array_diff($currentUserIds, $newUserIds);
            if (! empty($toRemove)) {
                $appointment->attendees()
                    ->whereIn('user_id', $toRemove)
                    ->where('user_id', '!=', $appointment->created_by)
                    ->delete();
            }

            // Yenileri ekle
            $toAdd = array_diff($newUserIds, $currentUserIds);
            foreach ($toAdd as $userId) {
                $appointment->attendees()->create([
                    'user_id' => $userId,
                    'status' => AttendeeStatus::Accepted,
                ]);
            }

            return $appointment->fresh();
        });
    }
}
