<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\CalendarType;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RemoveUserFromCompanyAction
{
    /**
     * Remove user from company.
     * Cleans up roles, calendars, and invitations.
     */
    public function execute(Company $company, User $user): void
    {
        DB::transaction(function () use ($company, $user) {
            // Remove Spatie roles
            setPermissionsTeamId($company->id);
            $user->syncRoles([]);

            // Delete user's personal calendar in this company
            $company->calendars()
                ->where('type', CalendarType::Personal->value)
                ->whereHas('users', fn ($q) => $q->where('user_id', $user->id)->where('role', 'owner'))
                ->delete();

            // Detach user from all company calendars
            $calendarIds = $company->calendars()->pluck('id');
            $user->calendars()->detach($calendarIds);

            // Delete ALL invitations for this user's email (including accepted ones)
            $company->invitations()->where('email', $user->email)->delete();

            // Detach from company pivot
            $company->users()->detach($user->id);
        });
    }
}
