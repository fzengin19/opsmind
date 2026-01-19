<?php

declare(strict_types=1);

use App\Enums\CalendarType;
use App\Models\Calendar;
use App\Models\Company;
use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

describe('CalendarPolicy', function () {
    it('allows any company user to view calendars', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('viewAny', Calendar::class))->toBeTrue();
    });

    it('allows owner to create calendar', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'owner');

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('create', Calendar::class))->toBeTrue();
    });

    it('allows admin to create calendar', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'admin');

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('create', Calendar::class))->toBeTrue();
    });

    it('denies member from creating calendar', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('create', Calendar::class))->toBeFalse();
    });

    it('allows calendar owner to update their calendar', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $calendar = Calendar::factory()->create([
            'company_id' => $company->id,
            'type' => CalendarType::Team,
        ]);
        $calendar->users()->attach($user->id, ['role' => 'owner']);

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('update', $calendar))->toBeTrue();
    });

    it('allows admin to update any company calendar', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $admin = User::factory()->create();
        $company->addUser($admin, 'admin');

        $calendar = Calendar::factory()->create([
            'company_id' => $company->id,
            'type' => CalendarType::Team,
        ]);

        $this->actingAs($admin);
        setPermissionsTeamId($company->id);

        expect($admin->can('update', $calendar))->toBeTrue();
    });

    it('prevents deletion of default calendar', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        // Note: Using admin instead of owner because Gate::before bypasses all policies for owner
        $user = User::factory()->create();
        $company->addUser($user, 'admin');

        $defaultCalendar = $company->defaultCalendar();

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('delete', $defaultCalendar))->toBeFalse();
    });

    it('prevents deletion of personal calendar', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        // Note: Using admin instead of owner because Gate::before bypasses all policies for owner
        $user = User::factory()->create();
        $company->addUser($user, 'admin');

        $personalCalendar = $company->calendars()
            ->where('type', CalendarType::Personal)
            ->first();

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('delete', $personalCalendar))->toBeFalse();
    });

    it('allows deletion of team calendar by admin', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $admin = User::factory()->create();
        $company->addUser($admin, 'admin');

        $calendar = Calendar::factory()->create([
            'company_id' => $company->id,
            'type' => CalendarType::Team,
        ]);

        $this->actingAs($admin);
        setPermissionsTeamId($company->id);

        expect($admin->can('delete', $calendar))->toBeTrue();
    });

    it('denies member from deleting calendar', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $member = User::factory()->create();
        $company->addUser($member, 'member');

        $calendar = Calendar::factory()->create([
            'company_id' => $company->id,
            'type' => CalendarType::Team,
        ]);

        $this->actingAs($member);
        setPermissionsTeamId($company->id);

        expect($member->can('delete', $calendar))->toBeFalse();
    });
});
