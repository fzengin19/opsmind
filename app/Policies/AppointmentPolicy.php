<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Determine whether the user can view any appointments.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentCompany() !== null;
    }

    /**
     * Determine whether the user can view the appointment.
     */
    public function view(User $user, Appointment $appointment): bool
    {
        // Aynı şirkette mi?
        if ($appointment->company_id !== $user->currentCompany()?->id) {
            return false;
        }

        // Calendar'a erişim var mı?
        if ($appointment->calendar) {
            return $appointment->calendar->isAccessibleBy($user);
        }

        // calendar_id null ise (eski randevular) şirket çalışanları görebilir
        return true;
    }

    /**
     * Determine whether the user can create appointments.
     */
    public function create(User $user): bool
    {
        return $user->currentCompany() !== null;
    }

    /**
     * Determine whether the user can update the appointment.
     */
    public function update(User $user, Appointment $appointment): bool
    {
        // Farklı şirketteyse izin yok
        if ($appointment->company_id !== $user->currentCompany()?->id) {
            return false;
        }

        // Owner/admin Gate::before ile bypass eder
        // Diğerleri sadece kendi oluşturduğunu düzenleyebilir
        return $appointment->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the appointment.
     */
    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->update($user, $appointment);
    }
}
