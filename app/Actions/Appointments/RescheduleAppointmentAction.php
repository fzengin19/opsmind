<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Models\Appointment;
use Carbon\Carbon;

class RescheduleAppointmentAction
{
    public function execute(Appointment $appointment, Carbon $start, Carbon $end): Appointment
    {
        $appointment->update([
            'start_at' => $start,
            'end_at' => $end,
        ]);

        return $appointment->fresh();
    }
}
