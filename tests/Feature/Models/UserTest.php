<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;

describe('User Model', function () {
    beforeEach(function () {
        $this->seed(\Database\Seeders\RoleSeeder::class);
    });

    it('belongs to companies', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'member');

        expect($user->companies)->toHaveCount(1);
    });

    it('can get current company', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'member');

        expect($user->currentCompany()->id)->toBe($company->id);
    });

    it('roleInCompany returns Spatie Role', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'admin');

        $role = $user->roleInCompany($company);
        expect($role)->not->toBeNull();
        expect($role->name)->toBe('admin');
    });

    it('isOwnerOf returns true for company owner', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'owner');

        expect($user->isOwnerOf($company))->toBeTrue();
    });

    it('isOwnerOf returns false for non-owner', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        $company->addUser($user, 'member');

        expect($user->isOwnerOf($company))->toBeFalse();
    });

    it('has initials method', function () {
        $user = User::factory()->create(['name' => 'John Doe']);

        expect($user->initials())->toBe('JD');
    });

    it('has hasCompany method', function () {
        $company = Company::factory()->create();
        createDefaultRolesForCompany($company);
        $user = User::factory()->create();

        expect($user->hasCompany())->toBeFalse();

        $company->addUser($user, 'member');

        $user->refresh();

        expect($user->hasCompany())->toBeTrue();
    });
});
