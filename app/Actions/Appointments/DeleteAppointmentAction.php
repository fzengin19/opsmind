<?php

declare(strict_types=1);

namespace App\Actions\Appointments;

use App\Models\Appointment;

class DeleteAppointmentAction
{
    public function execute(Appointment $appointment): void
    {
        // Attendees cascade ile silinir (FK constraint)
        $appointment->delete();
    }
}
