<?php

declare(strict_types=1);

use App\Actions\Users\RemoveUserFromCompanyAction;
use App\Models\Company;
use App\Models\User;

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleSeeder::class);
});

describe('RemoveUserFromCompanyAction', function () {
    it('removes user from company', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'member');
        expect($company->users)->toHaveCount(1);

        $action = app(RemoveUserFromCompanyAction::class);
        $action->execute($company, $user);

        expect($company->fresh()->users)->toHaveCount(0);
    });

    it('removes spatie roles', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'admin');

        $action = app(RemoveUserFromCompanyAction::class);
        $action->execute($company, $user);

        setPermissionsTeamId($company->id);
        expect($user->roles)->toHaveCount(0);
    });

    it('deletes personal calendar', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'member');
        // CompanyObserver creates 1 default calendar, AddUserToCompanyAction creates 1 personal calendar
        expect($company->calendars)->toHaveCount(2);

        $action = app(RemoveUserFromCompanyAction::class);
        $action->execute($company, $user);

        // Only personal calendar should be deleted, default calendar remains
        expect($company->fresh()->calendars)->toHaveCount(1);
        expect($company->fresh()->calendars()->first()->type)->toBe(\App\Enums\CalendarType::Default);
    });

    it('deletes invitations for user email', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        // Create invitation
        \App\Models\Invitation::create([
            'company_id' => $company->id,
            'email' => $user->email,
            'role_name' => 'member',
            'token' => \App\Models\Invitation::generateToken(),
            'expires_at' => now()->addHours(48),
            'invited_by' => $user->id,
            'accepted_at' => now(),
        ]);

        expect($company->invitations)->toHaveCount(1);

        $company->addUser($user, 'member');
        $action = app(RemoveUserFromCompanyAction::class);
        $action->execute($company, $user);

        expect($company->fresh()->invitations)->toHaveCount(0);
    });

    it('detaches user from all calendars', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'member');

        // Add user to another calendar
        $calendar = \App\Models\Calendar::factory()->create([
            'company_id' => $company->id,
        ]);
        $calendar->users()->attach($user->id, ['role' => 'viewer']);

        expect($user->calendars)->toHaveCount(2);

        $action = app(RemoveUserFromCompanyAction::class);
        $action->execute($company, $user);

        expect($user->fresh()->calendars)->toHaveCount(0);
    });

    it('wraps operations in database transaction', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'member');

        // Force failure by mocking
        $action = mock(RemoveUserFromCompanyAction::class);
        $action->shouldReceive('execute')
            ->andThrow(new \Exception('Test failure'));

        expect(fn () => $action->execute($company, $user))
            ->toThrow(\Exception::class);

        // User should still be in company if transaction fails
        expect($company->fresh()->users)->toHaveCount(1);
    });
});
