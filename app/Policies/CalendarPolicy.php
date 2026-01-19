<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\CalendarType;
use App\Models\Calendar;
use App\Models\User;

class CalendarPolicy
{
    /**
     * Şirket çalışanları takvim listesini görebilir.
     */
    public function viewAny(User $user): bool
    {
        return $user->currentCompany() !== null;
    }

    /**
     * Calendar::isAccessibleBy() ile belirlenir.
     */
    public function view(User $user, Calendar $calendar): bool
    {
        if ($calendar->company_id !== $user->currentCompany()?->id) {
            return false;
        }

        return $calendar->isAccessibleBy($user);
    }

    /**
     * Sadece owner/admin yeni Team/Resource takvim oluşturabilir.
     */
    public function create(User $user): bool
    {
        if ($user->currentCompany() === null) {
            return false;
        }

        setPermissionsTeamId($user->currentCompany()->id);

        return $user->hasAnyRole(['owner', 'admin']);
    }

    /**
     * Takvim düzenleme kuralları:
     * - Default/Personal: Sadece name ve color düzenlenebilir
     * - Team/Resource: Oluşturan veya admin/owner düzenleyebilir
     */
    public function update(User $user, Calendar $calendar): bool
    {
        if ($calendar->company_id !== $user->currentCompany()?->id) {
            return false;
        }

        setPermissionsTeamId($user->currentCompany()->id);

        // Personal takvim sadece owner tarafından düzenlenebilir
        if ($calendar->type === CalendarType::Personal) {
            return $calendar->users()
                ->where('user_id', $user->id)
                ->where('role', 'owner')
                ->exists();
        }

        // Default takvim şirket admin/owner'ı tarafından düzenlenebilir
        if ($calendar->is_default) {
            return $user->hasAnyRole(['owner', 'admin']);
        }

        // Team/Resource: Oluşturan (pivot owner) veya admin/owner
        $isCalendarOwner = $calendar->users()
            ->where('user_id', $user->id)
            ->where('role', 'owner')
            ->exists();

        return $isCalendarOwner || $user->hasAnyRole(['owner', 'admin']);
    }

    /**
     * Silme kuralları:
     * - Default takvim silinemez
     * - Personal takvim silinemez (sadece removeUser ile)
     * - Team/Resource: Admin/owner silebilir
     */
    public function delete(User $user, Calendar $calendar): bool
    {
        if ($calendar->company_id !== $user->currentCompany()?->id) {
            return false;
        }

        // Default ve Personal takvimler silinemez
        if ($calendar->is_default || $calendar->type === CalendarType::Personal) {
            return false;
        }

        setPermissionsTeamId($user->currentCompany()->id);

        // Sadece admin/owner silebilir
        return $user->hasAnyRole(['owner', 'admin']);
    }
}
