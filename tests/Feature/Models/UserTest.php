<?php

declare(strict_types=1);

use App\Enums\CompanyRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

describe('User Model', function () {
    it('can be created with factory', function () {
        $user = User::factory()->create();

        expect($user)
            ->toBeInstanceOf(User::class)
            ->id->toBeInt()
            ->name->toBeString()
            ->email->toBeString();
    });

    it('can belong to company via pivot', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $company->addUser($user, CompanyRole::Member);

        expect($user->companies)->toHaveCount(1);
        expect($user->currentCompany()->id)->toBe($company->id);
    });

    it('returns null for currentCompany when user has no company', function () {
        $user = User::factory()->create();

        expect($user->currentCompany())->toBeNull();
        expect($user->hasCompany())->toBeFalse();
    });

    it('hasCompany returns true when user belongs to a company', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $company->addUser($user, CompanyRole::Member);

        expect($user->hasCompany())->toBeTrue();
    });

    it('can get role in company', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $company->addUser($user, CompanyRole::Manager);

        expect($user->roleIn($company))->toBe(CompanyRole::Manager);
    });

    it('isOwnerOf returns true for company owner', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $company->addUser($user, CompanyRole::Owner);

        expect($user->isOwnerOf($company))->toBeTrue();
    });

    it('isOwnerOf returns false for non-owner', function () {
        $company = Company::factory()->create();
        $user = User::factory()->create();

        $company->addUser($user, CompanyRole::Member);

        expect($user->isOwnerOf($company))->toBeFalse();
    });

    it('has initials method', function () {
        $user = User::factory()->create(['name' => 'John Doe']);

        expect($user->initials())->toBe('JD');
    });

    it('handles single name for initials', function () {
        $user = User::factory()->create(['name' => 'John']);

        expect($user->initials())->toBe('J');
    });

    it('can have roles assigned', function () {
        Role::create(['name' => 'admin']);
        $user = User::factory()->create();
        $user->assignRole('admin');

        expect($user->hasRole('admin'))->toBeTrue();
    });
});
