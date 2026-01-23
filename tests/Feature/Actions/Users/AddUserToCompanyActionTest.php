<?php

declare(strict_types=1);

use App\Actions\Users\AddUserToCompanyAction;
use App\Models\Company;
use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

describe('AddUserToCompanyAction', function () {
    it('adds user to company with role', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $action = app(AddUserToCompanyAction::class);
        $action->execute($company, $user, 'admin');

        expect($company->fresh()->users)->toHaveCount(1);
        setPermissionsTeamId($company->id);
        expect($user->hasRole('admin'))->toBeTrue();
    });

    it('adds user with department and job title', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $department = \App\Models\Department::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create();

        $action = app(AddUserToCompanyAction::class);
        $action->execute($company, $user, 'member', $department->id, 'Developer');

        $pivot = $company->users()->first()->pivot;
        expect($pivot->department_id)->toBe($department->id);
        expect($pivot->job_title)->toBe('Developer');
    });

    it('creates personal calendar for user', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $action = app(AddUserToCompanyAction::class);
        $action->execute($company, $user, 'member');

        // CompanyObserver creates 1 default calendar, AddUserToCompanyAction creates 1 personal calendar
        expect($company->fresh()->calendars)->toHaveCount(2);

        // Find the personal calendar
        $personalCalendar = $company->calendars()->where('type', 'personal')->first();
        expect($personalCalendar)->not->toBeNull();
        expect($personalCalendar->users()->first()->pivot->role)->toBe('owner');
    });

    it('wraps operations in database transaction', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        // Force failure by using invalid role
        $action = app(AddUserToCompanyAction::class);

        expect(fn () => $action->execute($company, $user, 'invalid_role'))
            ->toThrow(\Exception::class);

        // User should not be attached if transaction fails
        expect($company->fresh()->users)->toHaveCount(0);
    });
});
