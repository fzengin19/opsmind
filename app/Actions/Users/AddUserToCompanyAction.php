<?php

declare(strict_types=1);

namespace App\Actions\Users;

use App\Enums\CalendarType;
use App\Enums\CalendarVisibility;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AddUserToCompanyAction
{
    /**
     * Add a user to company with role, department, and job title.
     * Creates personal calendar and assigns Spatie permissions.
     */
    public function execute(
        Company $company,
        User $user,
        string $roleName,
        ?int $departmentId = null,
        ?string $jobTitle = null
    ): void {
        DB::transaction(function () use ($company, $user, $roleName, $departmentId, $jobTitle) {
            // Attach user to company pivot
            $company->users()->attach($user->id, [
                'department_id' => $departmentId,
                'job_title' => $jobTitle,
                'joined_at' => now(),
            ]);

            // Assign Spatie role
            setPermissionsTeamId($company->id);
            $user->assignRole($roleName);

            // Create personal calendar for user
            $calendar = $company->calendars()->create([
                'name' => $user->name.' '.__('calendar.personal_calendar_suffix'),
                'type' => CalendarType::Personal->value,
                'visibility' => CalendarVisibility::Private->value,
                'is_default' => false,
                'color' => '#8b5cf6',
            ]);

            $calendar->users()->attach($user->id, ['role' => 'owner']);
        });
    }
}
