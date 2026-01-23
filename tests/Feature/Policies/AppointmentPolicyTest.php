<?php

declare(strict_types=1);

use App\Enums\CalendarType;
use App\Models\Appointment;
use App\Models\Calendar;
use App\Models\Company;
use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

describe('AppointmentPolicy', function () {
    it('allows any company user to view appointments', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('viewAny', Appointment::class))->toBeTrue();
    });

    it('denies user without company to view appointments', function () {
        $user = User::factory()->create();

        $this->actingAs($user);

        expect($user->can('viewAny', Appointment::class))->toBeFalse();
    });

    it('allows viewing appointment from same company with accessible calendar', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $calendar = Calendar::factory()->create([
            'company_id' => $company->id,
            'type' => CalendarType::Team,
        ]);
        $calendar->users()->attach($user->id, ['role' => 'owner']);

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'calendar_id' => $calendar->id,
        ]);

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('view', $appointment))->toBeTrue();
    });

    it('allows viewing appointment from same company without calendar', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'calendar_id' => null,
        ]);

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('view', $appointment))->toBeTrue();
    });

    it('denies viewing appointment from different company', function () {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        createDefaultRolesForCompany($company1);
        createDefaultRolesForCompany($company2);

        $user = User::factory()->create();
        $company1->addUser($user, 'member');

        $appointment = Appointment::factory()->create([
            'company_id' => $company2->id,
        ]);

        $this->actingAs($user);
        setPermissionsTeamId($company1->id);

        expect($user->can('view', $appointment))->toBeFalse();
    });

    it('allows any company user to create appointments', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('create', Appointment::class))->toBeTrue();
    });

    it('allows creator to update appointment', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('update', $appointment))->toBeTrue();
    });

    it('allows creator to delete appointment', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();
        $company->addUser($user, 'member');

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
        ]);

        $this->actingAs($user);
        setPermissionsTeamId($company->id);

        expect($user->can('delete', $appointment))->toBeTrue();
    });

    it('denies non-creator from updating appointment', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $company->addUser($creator, 'member');
        $company->addUser($otherUser, 'member');

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($otherUser);
        setPermissionsTeamId($company->id);

        expect($otherUser->can('update', $appointment))->toBeFalse();
    });

    it('denies non-creator from deleting appointment', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $company->addUser($creator, 'member');
        $company->addUser($otherUser, 'member');

        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'created_by' => $creator->id,
        ]);

        $this->actingAs($otherUser);
        setPermissionsTeamId($company->id);

        expect($otherUser->can('delete', $appointment))->toBeFalse();
    });

});
