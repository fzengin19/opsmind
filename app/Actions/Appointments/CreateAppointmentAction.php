<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Data\AppointmentData;
use App\Enums\AttendeeStatus;
use App\Models\Appointment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAppointmentAction
{
    public function execute(AppointmentData $data, User $user): Appointment
    {
        return DB::transaction(function () use ($data, $user) {
            $appointment = Appointment::create([
                'company_id' => $data->company_id,
                'calendar_id' => $data->calendar_id,
                'title' => $data->title,
                'type' => $data->type,
                'start_at' => $data->start_at,
                'end_at' => $data->end_at,
                'all_day' => $data->all_day,
                'location' => $data->location,
                'description' => $data->description,
                'created_by' => $user->id,
            ]);

            // Oluşturanı otomatik katılımcı olarak ekle
            $appointment->attendees()->create([
                'user_id' => $user->id,
                'status' => AttendeeStatus::Accepted,
            ]);

            // Seçilen diğer kullanıcıları ekle (otomatik onaylı)
            foreach ($data->attendee_user_ids ?? [] as $userId) {
                if ($userId !== $user->id) {
                    $appointment->attendees()->create([
                        'user_id' => $userId,
                        'status' => AttendeeStatus::Accepted,
                    ]);
                }
            }

            return $appointment;
        });
    }
}
